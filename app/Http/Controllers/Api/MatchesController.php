<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Models\Content;
use Illuminate\Support\Carbon;

class MatchesController extends Controller
{
    protected Client $client;
    protected string $referer = 'https://socolivev.co/';
    protected int $matchDuration = 5400; // 90 min in seconds

    public function __construct()
    {
        $this->client = new Client([
            'http_errors' => false,
            'headers'     => [
                'User-Agent' => request()->header('User-Agent', 'Custom-Agent'),
                'referer'    => $this->referer,
                'origin'     => 'https://json.vnres.co',
            ],
        ]);
    }

    public function index(Request $request)
    {
        // Dates: yesterday, today, tomorrow
        $dates = [-1, 0, 1];
        $dateStrings = array_map(fn($d) => Carbon::now('Asia/Yangon')->addDays($d)->format('Ymd'), $dates);

        // Fetch football matches (external API)
        $footballMatchesRaw = collect($dateStrings)
            ->flatMap(fn($date) => $this->fetchMatches($date))
            ->values();

        // Split matches by status
        $footballMatches = [
            'live'     => $footballMatchesRaw->where('match_status', 'live')->values(),
            'upcoming' => $footballMatchesRaw->where('match_status', 'vs')->values(),
            'finished' => $footballMatchesRaw->where('match_status', 'finished')->values(),
        ];

        // Fetch live and sport content (local DB)
        $showVipOnly = filter_var($request->query('show_vip', false), FILTER_VALIDATE_BOOLEAN);
        $perPage = (int) $request->query('per_page', 15);
        $page = (int) $request->query('page', 1);
        $select = ['id', 'title', 'profileImg', 'coverImg', 'tags', 'content', 'category', 'duration', 'isvip', 'created_at'];

        $liveQuery = Content::select($select)
            ->where('category', 'Live')
            ->orderBy('created_at', 'desc');
        $sportQuery = Content::select($select)
            ->where('category', 'Sport')
            ->orderBy('created_at', 'desc');
        if ($showVipOnly) {
            $liveQuery->where('isvip', true);
            $sportQuery->where('isvip', true);
        }
        $live = $liveQuery->paginate($perPage, ['*'], 'live_page', $page)->items();
        $sport = $sportQuery->paginate($perPage, ['*'], 'sport_page', $page)->items();

        // Return combined response
        return response()->json([
            'date'     => $dateStrings,
            'football' => $footballMatches,
            'live'     => $live,
            'sport'    => $sport,
        ], 200);
    }

    protected function fetchMatches(string $date): Collection
    {
        $res  = $this->client->get("https://json.vnres.co/match/matches_{$date}.json");
        $body = (string) $res->getBody();

        if (! preg_match('/matches_\d+\((.*)\)/', $body, $m)) {
            return collect();
        }

        $payload = json_decode($m[1], true);
        if (($payload['code'] ?? 0) !== 200 || ! isset($payload['data'])) {
            return collect();
        }

        $now = Carbon::now()->timestamp;

        return collect($payload['data'])->map(function ($it) use ($now) {
            // safe‑extract everything that might not exist
            $mt        = (int)(($it['matchTime'] ?? 0) / 1000);
            $homeName  = $it['hostName']   ?? null;
            $homeIcon  = $it['hostIcon']   ?? null;
            $awayName  = $it['guestName']  ?? null;
            $awayIcon  = $it['guestIcon']  ?? null;
            $league    = $it['subCateName'] ?? null;
            $homeScore = $it['homeScore']  ?? null;
            $awayScore = $it['awayScore']  ?? null;

            // determine status
            $matchDuration = $this->matchDuration ?? 5400; // 90 min
            $status = $now < $mt
                ? 'vs'
                : ($now <= $mt + $matchDuration ? 'live' : 'finished');

            // build match_score only if both scores are present
            $match_score = (is_numeric($homeScore) && is_numeric($awayScore))
                ? "{$homeScore} - {$awayScore}"
                : null;

            // Get roomNum from first anchor if available
            $roomNum = null;
            if (!empty($it['anchors']) && isset($it['anchors'][0]['anchor']['roomNum'])) {
                $roomNum = $it['anchors'][0]['anchor']['roomNum'];
            }

            // Convert match_time to readable UTC format
            $matchTimeMmt = Carbon::createFromTimestamp($mt, 'UTC')
                ->setTimezone('Asia/Yangon')
                ->format('Y-m-d H:i:s') . ' MMT';

            return [
                'match_time'       => $matchTimeMmt,
                'match_time_unix'  => $mt,
                'match_status'     => $status,
                'home_team_name'   => $homeName,
                'home_team_logo'   => $homeIcon,
                'away_team_name'   => $awayName,
                'away_team_logo'   => $awayIcon,
                'league_name'      => $league,
                'match_score'      => $match_score,
                'room_num'         => $roomNum,
            ];
        });
    }

    protected function fetchServerURL(int $roomNum): array
    {
        try {
            $res  = $this->client->get("https://json.vnres.co/room/{$roomNum}/detail.json");
            $body = (string) $res->getBody();

            if (preg_match('/detail\((.*)\)/', $body, $m)) {
                $js = json_decode($m[1], true);
                if (($js['code'] ?? 0) === 200 && isset($js['data']['stream'])) {
                    return [
                        'm3u8'   => $js['data']['stream']['m3u8']   ?? null,
                        'hdM3u8' => $js['data']['stream']['hdM3u8'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("room {$roomNum} error: " . $e->getMessage());
        }

        return ['m3u8' => null, 'hdM3u8' => null];
    }

    // New route handler for servers for live matches
    public function getLiveServers(Request $request, int $roomNum)
    {
        $urls = $this->fetchServerURL($roomNum);
        $servers = [];
        if ($urls['m3u8']) {
            $servers[] = [
                'name' => 'Soco SD',
                'stream_url' => $urls['m3u8'],
                'referer' => $this->referer,
            ];
        }
        if ($urls['hdM3u8']) {
            $servers[] = [
                'name' => 'Soco HD',
                'stream_url' => $urls['hdM3u8'],
                'referer' => $this->referer,
            ];
        }
        return response()->json($servers);
    }
}
