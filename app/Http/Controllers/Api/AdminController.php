<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Content;
use App\Models\Subscription;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function listDevices(Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $page = $request->query('page', 1);

        $devices = Device::query()
            ->when($request->has('vip'), function ($q) use ($request) {
                $q->where('is_vip', filter_var($request->vip, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($request->has('platform'), function ($q) use ($request) {
                $q->where('platform', $request->platform);
            })
            ->when($request->has('user'), function ($q) use ($request) {
                $q->where('user_identifier', $request->user);
            })
            ->when($request->has('active'), function ($q) {
                $q->where('last_active_at', '>', now()->subDays(30));
            })
            ->orderBy('last_active_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'devices' => $devices->items(),
            'pagination' => [
                'total' => $devices->total(),
                'per_page' => $devices->perPage(),
                'current_page' => $devices->currentPage(),
                'last_page' => $devices->lastPage()
            ]
        ]);
    }

    public function getDevice($device_id)
    {
        $device = Device::with(['user', 'subscription'])
            ->where('device_id', $device_id)
            ->firstOrFail();

        return response()->json([
            'device' => $device,
            'views_count' => $device->views()->count(),
            'is_active' => $device->last_active_at > now()->subDays(30)
        ]);
    }

    public function setVipStatus(Request $request, $device_id)
    {
        $request->validate([
            'is_vip' => 'required|boolean',
            'expires_at' => 'nullable|date'
        ]);

        $device = Device::where('device_id', $device_id)->firstOrFail();

        $device->update([
            'is_vip' => $request->is_vip,
            'vip_expires_at' => $request->expires_at
        ]);

        return response()->json([
            'message' => 'VIP status updated',
            'device' => [
                'device_id' => $device->device_id,
                'is_vip' => $device->is_vip,
                'vip_expires_at' => $device->vip_expires_at
            ]
        ]);
    }

    public function deleteDevice($device_id)
    {
        $device = Device::where('device_id', $device_id)->firstOrFail();
        $device->delete();

        return response()->json(['message' => 'Device deleted successfully']);
    }
    public function getContentDetails(Request $request, $id)
    {
        $content = Content::withCount('views')->findOrFail($id);

        $relatedContent = $this->getRelatedContent($content);
        $response = [
            'content' => $content->makeHidden(['created_at', 'updated_at']),
            'views_count' => $content->views_count,
            'related_content' => $relatedContent,

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
    public function deleteContent($id)
    {
        try {
            // Find the content or fail with 404
            $content = Content::findOrFail($id);



            // Delete the content
            $content->delete();

            return response()->json([
                'success' => true,
                'message' => 'Content deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete content',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function deactivateSubscription(Request $request, $id)
    {
        $subscription = Subscription::where("key", $id)->first();
        $subscription->update(['is_active' => false]);
        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }
        return response()->json(["subscription" => $subscription    , 'message' => 'Subscription deactivated successfully']);
    }
    //
    public function createSubscriptionKey(Request $request)
    {
        $request->validate([
            'type' => 'required|in:1month,3months,lifetime',
            'max_devices' => 'required|integer|min:1|max:5' // Max 5 devices per key
        ]);

        $subscription = Subscription::create([
            'key' => Subscription::generateVerificationKey(),
            'type' => $request->type,
            'expires_at' => Subscription::calculateExpiration($request->type),
            'max_devices' => $request->max_devices,
            'devices_used' => 0,
            'is_active' => true
        ]);

        return response()->json([
            'message' => 'Subscription key created',
            'key' => $subscription->key,
            'type' => $subscription->type,
            'is_active' => true,
            'devices_used' => 0,
            'max_devices' => $subscription->max_devices,
            'expires_at' => $subscription->expires_at

        ]);
    }

    public function listSubscriptionKeys()
    {
        $keys = Subscription::all();

        return response()->json([
            'subscription_keys' => $keys->map(function ($key) {
                return [
                    'key' => $key->key,
                    'type' => $key->type,
                    'max_devices' => $key->max_devices,
                    'devices_used' => $key->devices_used,
                    'is_active' => $key->is_active,
                    'expires_at' => $key->expires_at
                ];
            })
        ]);
    }
    public function upgradeToVip(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,device_id',
            'type' => 'required|in:1month,3months,lifetime'
        ]);

        $device = Device::where('device_id', $request->device_id)->first();

        $expiresAt = Subscription::calculateExpiration($request->type);

        $device->update([
            'is_vip' => true,
            'vip_expires_at' => $expiresAt
        ]);

        return response()->json([
            'message' => 'User upgraded to VIP',
            'type' => $request->type,
            'expires_at' => $expiresAt
        ]);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'profileImg' => 'nullable|string',
            'coverImg' => 'nullable|string',
            'duration' => 'nullable|string',
            'links' => 'nullable|array',
            'links.*' => 'url',
            'content' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'category' => 'nullable|string|max:100',
            'casts' => 'nullable|array',
            'casts.*.name' => 'required|string|max:100',
            'casts.*.role' => 'required|string|max:100',
            'files' => 'nullable|array',
            'files.traller' => 'nullable|array',
            'files.traller.url' => 'nullable|url',
            'files.traller.quality' => 'nullable|string',
            'files.traller.size' => 'nullable|string',
            'files.stream' => 'nullable|array',
            'files.stream.*.url' => 'nullable|url',
            'files.stream.*.quality' => 'nullable|string',
            'files.stream.*.size' => 'nullable|string',
            'files.download' => 'nullable|array',
            'files.download.*.url' => 'nullable|url',
            'files.download.*.quality' => 'nullable|string',
            'files.download.*.size' => 'nullable|string',
            'isvip' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $content = Content::create($request->all());

        return response()->json([
            'message' => 'Content created successfully',
            'data' => $content
        ], 201);
    }
    public function createContent(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'profileImg' => 'nullable|url',
            'coverImg' => 'nullable|url',
            'links' => 'nullable|array',
            'content' => 'required|string',
            'tags' => 'nullable|array',
            'isvip' => 'required|boolean',
            'files' => 'nullable|array',
            'files.trailer' => 'nullable',
            'files.stream' => 'nullable',
            'files.download' => 'nullable'
        ]);

        $content = Content::create($request->all());

        return response()->json($content, 201);
    }

    public function updateContent(Request $request, $id)
    {
        // Find the content or return 404
        $content = Content::find($id);
        if (!$content) {
            return response()->json([
                'message' => 'Content not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'profileImg' => 'nullable|string',
            'coverImg' => 'nullable|string',
            'duration' => 'string',
            'links' => 'nullable|array',
            'links.*' => 'url',
            'content' => 'sometimes|required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'category' => 'string|max:100',
            'casts' => 'nullable|array',
            'casts.*.name' => 'required_with:casts|string|max:100',
            'casts.*.role' => 'required_with:casts|string|max:100',
            'files' => 'nullable|array',
            'files.traller' => 'nullable|array',
            'files.traller.url' => 'nullable|url',
            'files.traller.quality' => 'nullable|string',
            'files.traller.size' => 'nullable|string',
            'files.stream' => 'nullable|array',
            'files.stream.*.url' => 'nullable|url',
            'files.stream.*.quality' => 'nullable|string',
            'files.stream.*.size' => 'nullable|string',
            'files.download' => 'nullable|array',
            'files.download.*.url' => 'nullable|url',
            'files.download.*.quality' => 'nullable|string',
            'files.download.*.size' => 'nullable|string',
            'isvip' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // Update the content
            $content->update($request->all());

            return response()->json([
                'message' => 'Content updated successfully',
                'data' => $content
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating content',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
