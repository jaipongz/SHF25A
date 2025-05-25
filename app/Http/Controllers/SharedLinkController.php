<?php
namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\SharedLink;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class SharedLinkController extends Controller
{
    // สร้างลิงก์แชร์
    public function create($itemId)
    {
        $item = Item::findOrFail($itemId);

        $link = SharedLink::create([
            'item_id' => $item->id,
            'shared_token' => Str::random(32),
            'is_active' => true,
        ]);

        return response()->json([
            'link' => route('shared.show', $link->shared_token),
        ]);
    }

    // เข้าดูไฟล์ที่แชร์
    // public function show($token)
    // {
    //     $link = SharedLink::where('shared_token', $token)->where('is_active', true)->firstOrFail();
    //     $item = $link->item;

    //     return view('shared_links.show', compact('item'));
    // }
    public function show($token)
    {
        $link = SharedLink::where('shared_token', $token)->where('is_active', true)->firstOrFail();
        $item = $link->item;

        if ($item->type === 'folder') {
            $query = Item::where('parent_id', $item->id)
                ->where('is_deleted', false)
                ->whereNull('deleted_at');

            $items = $query->get();
            $totalItems = $items->count();

            $breadcrumbs = [];
            $current = $item;

            while ($current) {
                $breadcrumbs[] = $current;
                $current = $current->parent;
            }

            $breadcrumbs = array_reverse($breadcrumbs);
        } else {
            // ถ้าแชร์เป็นไฟล์เดี่ยว
            $items = collect([$item]);
            $totalItems = 1;
            $breadcrumbs = [$item];
        }

        // จัดข้อมูลให้เหมือน groupedItems
        $groupedItems = $items->map(function ($item) {
            $extension = strtolower(pathinfo($item->name, PATHINFO_EXTENSION));

            $fileType = match (true) {
                in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) => 'image',
                in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']) => 'document',
                in_array($extension, ['mp4', 'webm', 'avi', 'mov']) => 'video',
                default => 'other',
            };

            return [
                ...$item->toArray(),
                'file_type' => $fileType,
                'extension' => $extension,
                'url' => $item->path,
            ];
        });

        // ทดสอบผลลัพธ์
        return view('shared_links.show', [
            'items' => $groupedItems,
            'totalItems' => $totalItems,
            'breadcrumbs' => $breadcrumbs,
            'isShared' => true
        ]);
    }

    public function index(Request $request)
    {
        $parentId = $request->query('parent_id', null);
        $search = $request->query('search', null);
        $pageIndex = (int) $request->query('index', 1);
        $limit = (int) $request->query('limit', 20);

        $query = Item::where('is_deleted', false)
            ->where('parent_id', $parentId)
            ->whereNull('deleted_at');

        // ถ้ามี search string ให้กรองข้อมูลด้วย
        if ($search) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        // นับจำนวนทั้งหมดก่อน pagination
        $totalItems = $query->count();

        // คำนวณ offset แล้วดึงข้อมูลตาม index/limit
        $items = $query->skip(($pageIndex - 1) * $limit)
            ->take($limit)
            ->get();

        // ดึง breadcrumb path
        $breadcrumbs = [];
        $current = $parentId ? Item::find($parentId) : null;

        while ($current) {
            $breadcrumbs[] = $current;
            $current = $current->parent; // ต้องมี relation `parent()` ใน Model
        }
        $breadcrumbs = array_reverse($breadcrumbs); // จาก root → ปัจจุบัน

        $groupedItems = $items->map(function ($item) {
            $extension = strtolower(pathinfo($item->name, PATHINFO_EXTENSION));

            $fileType = match (true) {
                in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) => 'image',
                in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']) => 'document',
                in_array($extension, ['mp4', 'webm', 'avi', 'mov']) => 'video',
                default => 'other',
            };

            return [
                ...$item->toArray(),
                'file_type' => $fileType,
                'extension' => $extension,
                'url' => $item->path, // ใช้แสดง preview หรือดาวน์โหลด
            ];
        });
        return view('shared_links.show', [
            'items' => $groupedItems,
            'parentId' => $parentId,
            'search' => $search,
            'pageIndex' => $pageIndex,
            'limit' => $limit,
            'totalItems' => $totalItems,
            'totalPages' => ceil($totalItems / $limit),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

}

