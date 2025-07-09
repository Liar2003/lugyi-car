<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Content;

use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Device;
use App\Models\Suggestion;


class ContentController extends Controller
{
    public function search(Request $request)
    {
        $search = $request->input('q');
        $perPage = (int) $request->query('per_page', 6);
        $page = (int) $request->query('page', 1);

        $contents = Content::when($search, function ($query, $search) {
            return $query->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%");
        })->select('id', 'title', 'profileImg', 'coverImg', 'content', 'tags', 'isvip', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        return response()->json([
            'search' => $search,
            'contents' => $contents->items(),
            'pagination' => [
                'total' => $contents->total(),
                'per_page' => $contents->perPage(),
                'current_page' => $contents->currentPage(),
                'last_page' => $contents->lastPage(),
                'from' => $contents->firstItem(),
                'to' => $contents->lastItem()
            ]
        ]);
    }
    use Illuminate\Support\Facades\DB;

public function getHomeContents(Request $request)
{
    $categories = [
        'jav'      => ['name' => 'Jav',    'vip' => false],
        'thai'     => ['name' => 'Thai',   'vip' => false],
        'chinese'  => ['name' => 'Chinese','vip' => false],
        'mm_sub'   => ['name' => 'MMsub',  'vip' => false],
        'usa'      => ['name' => 'USA',    'vip' => true], // Only VIP for USA
        'korea'    => ['name' => 'Korea',  'vip' => false],
        'movies'   => ['name' => 'Movie',  'vip' => false],
    ];

    $selectColumns = ['id', 'title', 'profileImg', 'content', 'tags', 'isvip', 'created_at'];
    $results = [];
    $pagination = [];

    // Step 1: Fetch counts in 2 optimized queries
    $nonUsaCats = collect($categories)->filter(fn($cat) => !$cat['vip'])
                                      ->pluck('name');
    
    $counts = Content::whereIn('category', $nonUsaCats)
                     ->where('isvip', false)
                     ->selectRaw('category, count(*) as total')
                     ->groupBy('category')
                     ->pluck('total', 'category');

    $usaCount = Content::where('category', 'USA')
                       ->where('isvip', true)
                       ->count();

    // Step 2: Fetch category data in parallel
    $categoryData = [];
    foreach ($categories as $key => $cat) {
        $query = Content::where('category', $cat['name'])
                        ->where('isvip', $cat['vip'])
                        ->select($selectColumns)
                        ->latest();

        $categoryData[$key] = $query->take(8)->get();
        $pagination['total_' . $key] = $cat['vip'] ? $usaCount : ($counts[$cat['name']] ?? 0);
    }

    // Step 3: Fetch special content
    $specialContent = [
        'vip_contents' => Content::where('category', 'Jav')
                                ->where('isvip', true)
                                ->select($selectColumns)
                                ->latest()
                                ->take(8)
                                ->get(),
        
        'liveAndsport' => Content::whereIn('category', ['Live', 'Sport'])
                                ->select(['id', 'title', 'profileImg', 'coverImg', 'tags', 'content', 'category', 'duration', 'isvip', 'created_at'])
                                ->latest()
                                ->take(8)
                                ->get()
    ];

    // Step 4: Additional data
    $additionalData = [
        'categories'  => Category::all(),
        'suggestions' => Suggestion::all(),
        'device'      => Device::where('api_token', $request->bearerToken())->first(),
    ];

    // Step 5: Handle ads
    $ad = [];
    if (!$additionalData['device']?->isVip()) {
        $ad = ['ad' => [['link' => '', 'imgUrl' => 'https://i.postimg.cc/HWB1dgMj/IMG-20250705-190417-694.jpg']]];
    }

    // Compile final response
    return response()->json([
        ...$categoryData,
        ...$specialContent,
        ...$additionalData,
        ...$ad,
        'pagination' => $pagination
    ]);
}

    public function getContentsByCategory(Request $request, $category)
    {
        $request->validate([
            'show_vip' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1'
        ]);

        // Proper boolean conversion
        $showVipOnly = filter_var($request->query('show_vip', false), FILTER_VALIDATE_BOOLEAN);
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        $device = $request->device;

        $query = Content::where('category', $category)
            ->select('id', 'title', 'profileImg', 'coverImg', 'content', 'tags', 'isvip', 'created_at')->orderBy('created_at', 'desc');

        if ($showVipOnly) {
            $query->where('isvip', true);
        } else {
            $query->where('isvip', false);
        }

        // if (!$device || !$device->isVip()) {
        //     $query->where('isvip', false);
        // }

        $contents = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'category' => $category,
            'filter' => [
                'vip_only' => $showVipOnly,
                'user_has_vip_access' => $device && $device->isVip()
            ],
            'contents' => $contents->items(),
            'pagination' => [
                'total' => $contents->total(),
                'per_page' => $contents->perPage(),
                'current_page' => $contents->currentPage(),
                'last_page' => $contents->lastPage(),
                'from' => $contents->firstItem(),
                'to' => $contents->lastItem()
            ]
        ]);
    }
    public function getContentsByCast(Request $request, $cast)
    {
        // Get pagination parameters (default: page=1, per_page=15)
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);

        // Search for contents with the tag
        $query = Content::whereRaw("EXISTS (
            SELECT 1 
            FROM json_array_elements(
                CASE 
                    WHEN casts IS NULL THEN '[]'::json
                    WHEN json_typeof(casts) = 'array' THEN casts 
                    ELSE '[]'::json 
                END
            ) AS c 
            WHERE LOWER(c->>'name') = LOWER(?)
        )", [$cast])
            ->select('id', 'title', 'profileImg', 'coverImg', 'content', 'tags', 'isvip', 'created_at')
            ->orderBy('created_at', 'desc');

        $contents = $query->paginate($perPage, ['*'], 'page', $page);

        // $contents = Content::whereJsonContains('casts', $cast)
        //     ->select('id', 'title', 'profileImg', 'coverImg', 'tags', 'content', 'isvip', 'created_at')
        //     ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'casts' => $cast,
            'contents' => $contents->items(),
            'pagination' => [
                'total' => $contents->total(),
                'per_page' => $contents->perPage(),
                'current_page' => $contents->currentPage(),
                'last_page' => $contents->lastPage(),
                'from' => $contents->firstItem(),
                'to' => $contents->lastItem()
            ]
        ]);
    }

    // public function getContentsByCategory(Request $request, $category)
    // {
    //     // Get pagination parameters (default: page=1, per_page=15)
    //     $perPage = $request->query('per_page', 15);
    //     $page = $request->query('page', 1);

    //     // Search for contents with the category
    //     $contents = Content::where('category', $category)
    //         ->select('id', 'title', 'profileImg', 'coverImg', 'tags', 'isvip', 'created_at')
    //         ->paginate($perPage, ['*'], 'page', $page);

    //     return response()->json([
    //         'category' => $category,
    //         'contents' => $contents->items(),
    //         'pagination' => [
    //             'total' => $contents->total(),
    //             'per_page' => $contents->perPage(),
    //             'current_page' => $contents->currentPage(),
    //             'last_page' => $contents->lastPage(),
    //             'from' => $contents->firstItem(),
    //             'to' => $contents->lastItem()
    //         ]
    //     ]);
    // }
    public function getContentsByTag(Request $request, $tag)
    {
        // Get pagination parameters (default: page=1, per_page=15)
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);

        // Search for contents with the tag
        $contents = Content::whereJsonContains('tags', $tag)
            ->select('id', 'title', 'profileImg', 'coverImg', 'tags', 'content', 'isvip', 'created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'tag' => $tag,
            'contents' => $contents->items(),
            'pagination' => [
                'total' => $contents->total(),
                'per_page' => $contents->perPage(),
                'current_page' => $contents->currentPage(),
                'last_page' => $contents->lastPage(),
                'from' => $contents->firstItem(),
                'to' => $contents->lastItem()
            ]
        ]);
    }
    //
    public function listContents(Request $request)
    {
        $showVipOnly = filter_var($request->query('show_vip', false), FILTER_VALIDATE_BOOLEAN);

        $query = Content::select('id', 'title', 'profileImg', 'coverImg', 'tags', 'content', 'category', 'duration', 'isvip', 'created_at')
            ->whereNotIn('category', ['Live', 'Sport']) // Exclude "Live" and "Sport"
            ->orderBy('created_at', 'desc');

        if ($showVipOnly) {
            $query->where('isvip', true);
        }

        $contents = $query->paginate(15, ['*'], 'page', $request->query('page', 1));

        return response()->json($contents);
    }
    //
    public function listLiveAndSportContents(Request $request)
    {
        $showVipOnly = filter_var($request->query('show_vip', false), FILTER_VALIDATE_BOOLEAN);

        $query = Content::select('id', 'title', 'profileImg', 'coverImg', 'tags', 'content', 'category', 'duration', 'isvip', 'created_at')
            ->whereIn('category', ['Live', 'Sport']) // Only include "Live" and "Sport"
            ->orderBy('created_at', 'desc');

        if ($showVipOnly) {
            $query->where('isvip', true);
        }

        $contents = $query->paginate(15, ['*'], 'page', $request->query('page', 1));

        return response()->json($contents);
    }
    public function listLiveAndSport(Request $request)
    {
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

        return response()->json([
            'live' => $live,
            'sport' => $sport,
        ]);
    }



    // Update getContentDetails method
    public function getContentDetails(Request $request, $id)
    {
        $content = Content::withCount('views')->findOrFail($id);
        $device = $request->device;

        // Check VIP access
        if ($content->isvip && (!$device || !$device->isVip())) {
            $relatedContent = $this->getRelatedContent($content);
            $response = [
                'content' => $content->makeHidden(['files', 'created_at', 'updated_at']),
                'views_count' => $content->views_count,
                'related_content' => $relatedContent,
                'msg' => 'VIP content requires VIP access',

            ];
            return response()->json($response);
        }
        // Get related content (by tags)
        $relatedContent = $this->getRelatedContent($content);
        $response = [
            'content' => $content->makeHidden(['created_at', 'updated_at']),
            'views_count' => $content->views_count,
            'related_content' => $relatedContent,
            "msg" => "Success"

        ];

        return response()->json($response);
    }
    private function getRelatedContent(Content $content, $limit = 5)
    {
        $query = Content::where('id', '!=', $content->id);

        // Match by category if exists
        if ($content->category) {
            $query->where('category', $content->category);
        }

        // Match by tags if exists
        if (!empty($content->tags)) {
            $query->orWhere(function ($q) use ($content) {
                foreach ($content->tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        return $query->select('id', 'title', 'profileImg', 'coverImg', 'content', 'tags', 'isvip', 'category', 'created_at')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return $this->formatRelatedContent($item);
            });
    }
    private function formatRelatedContent($content)
    {
        return [
            'id' => $content->id,
            'title' => $content->title,
            'profileImg' => $content->profileImg,
            'coverImg' => $content->coverImg,
            'isvip' => $content->isvip,
            'tags' => $content->tags,
            'short_description' => Str::limit($content->content, 100)
        ];
    }

    // Add new method for view statistics
    public function getContentViews($id)
    {
        $content = Content::with(['views' => function ($query) {
            $query->latest()->take(100);
        }])->findOrFail($id);

        return response()->json([
            'content_id' => $content->id,
            'total_views' => $content->views->count(),
            'recent_views' => $content->views->map(function ($view) {
                return [
                    'viewed_at' => $view->created_at,
                    'device' => $view->device ? $view->device->device_id : null,
                    'ip_address' => $view->ip_address,
                    'user_agent' => $view->user_agent
                ];
            })
        ]);
    }

    public function normalContents(Request $request)
    {
        // Show only non-VIP content to all users
        $contents = Content::where('isvip', false)
            ->select('id', 'title', 'profileImg', 'coverImg', 'content', 'tags', 'isvip', 'created_at')
            ->get();

        return response()->json($contents);
    }

    public function vipContents(Request $request)
    {
        // Only VIP users can access this route (enforced by middleware)
        $contents = Content::where('isvip', true)
            ->select('id', 'title', 'profileImg', 'content', 'coverImg', 'tags', 'isvip', 'created_at')
            ->get();

        return response()->json($contents);
    }

    public function upgradeInfo()
    {
        return response()->json([
            'message' => 'Upgrade to VIP for full access',
            'contact' => 'support@example.com'
        ]);
    }
}
