<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\SharedLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $parentId = $request->query('parent_id', null);
        $search = $request->query('search', null);
        $pageIndex = (int) $request->query('index', 1);
        $limit = (int) $request->query('limit', 20);

        $query = Item::where('user_id', Auth::id())
            ->where('is_deleted', false)
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
        return view('items.index', [
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

    public function dashboard()
    {
        $userId = Auth::id();

        // 1. จำนวนไฟล์ทั้งหมด (ไม่ถูกลบ และไม่อยู่ในถังขยะ)
        $fileCount = Item::where('user_id', $userId)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->where('type', 'file')
            ->count();

        // 2. จำนวนโฟลเดอร์ทั้งหมด (ไม่ถูกลบ และไม่อยู่ในถังขยะ)
        $folderCount = Item::where('user_id', $userId)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->where('type', 'folder')
            ->count();

        // 3. จำนวนรายการในถังขยะ
        $trashCount = Item::withTrashed()
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('is_deleted', 1)
                    ->orWhereNotNull('deleted_at');
            })->count();
        // dd($trashCount);
        $sharedCount = SharedLink::whereHas('item', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->count();
        $totalSizeBytes = Item::where('user_id', $userId)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->where('type', 'file')
            ->sum('size');

        $totalSizeGB = $this->formatBytes($totalSizeBytes);
        $sizeInGB = round($totalSizeBytes / (1024 ** 3), 2);
        return view('dashboard', compact(
            'fileCount',
            'folderCount',
            'trashCount',
            'sharedCount',
            'totalSizeGB',
            'sizeInGB'
        ));
    }

    public function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function update(Request $request, Item $item)
    {
        // $this->authorize('update', $item); // ถ้าใช้ policy

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $item->update($validated);

        return redirect()->route('items.index', ['parent_id' => $item->parent_id])->with('success', 'แก้ไขสำเร็จ');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:file,folder',
            'parent_id' => 'nullable|exists:items,id',
            'path' => 'nullable|string',
            'size' => 'nullable|integer',
        ]);

        $validated['user_id'] = Auth::id();
        Item::create($validated);

        return redirect()->back()->with('success', 'สร้างสำเร็จ');
    }

    // public function upload(Request $request)
    // {
    //     $request->validate([
    //         'files' => 'required|array',
    //         'files.*' => 'file|max:1536000', // สูงสุด 10MB ต่อไฟล์
    //         'parent_id' => 'nullable|exists:items,id',
    //     ]);

    //     $userId = auth()->id();
    //     $parentId = $request->input('parent_id');

    //     foreach ($request->file('files') as $file) {
    //         $originalName = $file->getClientOriginalName();
    //         $extension = $file->getClientOriginalExtension();
    //         $size = $file->getSize();

    //         // Generate unique filename
    //         $filename = Str::uuid() . '.' . $extension;

    //         // Store the file in storage/app/uploads
    //         $storedPath = $file->storeAs('uploads', $filename, 'public');


    //         // Save metadata to database
    //         Item::create([
    //             'name' => $originalName,
    //             'type' => 'file',
    //             'parent_id' => $parentId,
    //             'path' => $storedPath,
    //             'size' => $size,
    //             'user_id' => $userId,
    //         ]);
    //     }

    //     return redirect()->back()->with('success', 'อัปโหลดไฟล์สำเร็จแล้ว');
    // }
    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|max:1536000', // สูงสุด ~1.5GB ต่อไฟล์
            'parent_id' => 'nullable|exists:items,id',
        ]);

        $userId = Auth::id();
        $parentId = $request->input('parent_id');

        $uploadPath = public_path('assets/uploads');

        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }

        foreach ($request->file('files') as $file) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();

            // สร้างชื่อไฟล์ไม่ให้ซ้ำ
            $filename = Str::uuid() . '.' . $extension;

            // ย้ายไฟล์ไปยัง public/assets/uploads
            $file->move($uploadPath, $filename);

            // บันทึกข้อมูลลง DB
            Item::create([
                'name' => $originalName,
                'type' => 'file',
                'parent_id' => $parentId,
                'path' => 'assets/uploads/' . $filename, // เส้นทางที่ใช้ใน browser
                'size' => $size,
                'user_id' => $userId,
            ]);
        }

        return redirect()->back()->with('success', 'อัปโหลดไฟล์สำเร็จแล้ว');
    }
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!empty($ids)) {
            Item::softDeleteWithChildren($ids, Auth::id());
        }

        return response()->json(['message' => 'ย้ายไปถังขยะแล้ว']);
    }

    public function bulkRestore(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!empty($ids)) {
            // กู้คืนไฟล์หรือโฟลเดอร์ที่ถูกลบ (parent)
            Item::onlyTrashed() // ใช้กับ Trashed records
                ->whereIn('id', $ids)
                ->where('user_id', Auth::id())
                ->restore();

            // กู้คืนทุก child ของ parent ที่ถูกลบ
            Item::onlyTrashed() // ใช้กับ Trashed records
                ->whereIn('parent_id', $ids)
                ->where('user_id', Auth::id())
                ->restore();
        }

        return response()->json([
            'message' => 'กู้คืนเรียบร้อย',
            'success' => 'ข้อมูลได้รับการกู้คืนแล้ว'
        ]);
    }

    // ลบแบบ soft delete พร้อม children
    public function destroy(Item $item)
    {
        $this->recursiveSoftDelete($item);
        return redirect()->route('items.index')->with('success', 'ย้ายไปถังขยะแล้ว');
    }

    // กู้คืนจากถังขยะ
    public function restore($id)
    {
        $item = Item::withTrashed()->findOrFail($id);
        // $this->recursiveRestore($item);
        return redirect()->route('items.trash')->with('success', 'กู้คืนแล้ว');
    }

    // แสดงไฟล์ในถังขยะ
    // public function trash()
    // {
    //     $items = Item::onlyTrashed()
    //         ->where('is_deleted', true)
    //         ->get();

    //     return view('items.trash', compact('items'));
    // }

    public function trash(Request $request)
    {
        $pageIndex = (int) $request->query('index', 1);
        $limit = (int) $request->query('limit', 20);
        $search = $request->query('search', null);

        $query = Item::onlyTrashed()
            ->where('is_deleted', true)
            ->where('user_id', Auth::id());

        if ($search) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        $totalItems = $query->count();

        $items = $query->skip(($pageIndex - 1) * $limit)
            ->take($limit)
            ->get();

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
                'url' => $item->path, // แสดง preview หรือดาวน์โหลด
            ];
        });

        return view('items.trash', [
            'items' => $groupedItems,
            'pageIndex' => $pageIndex,
            'limit' => $limit,
            'totalItems' => $totalItems,
            'totalPages' => ceil($totalItems / $limit),
            'search' => $search,
        ]);
    }



    // 🔁 ฟังก์ชันช่วย - soft delete แบบ recursive
    private function recursiveSoftDelete(Item $item)
    {
        $item->is_deleted = true;
        $item->deleted_at = now();
        $item->save();

        foreach ($item->children as $child) {
            $this->recursiveSoftDelete($child);
        }

        $item->delete();
    }

    // 🔁 ฟังก์ชันช่วย - restore แบบ recursive
    private function recursiveRestore(Item $item)
    {
        $item->update(['is_deleted' => false, 'deleted_at' => null]);

        foreach ($item->children as $child) {
            $this->recursiveRestore($child);
        }
    }

    public function bulkActionTrash(Request $request)
    {
        $ids = $request->input('ids', []);
        $action = $request->input('action');
        // dd($request);
        if (empty($ids)) {
            return redirect()->back()->with('error', 'ยังไม่ได้เลือกรายการ');
        }

        if ($action === 'restore') {
            // แก้ flag is_deleted
            Item::withTrashed()
                ->whereIn('id', $ids)
                ->where('user_id', Auth::id())
                ->update(['is_deleted' => false]);

            // กู้คืน soft delete
            Item::withTrashed()
                ->whereIn('id', $ids)
                ->where('user_id', Auth::id())
                ->restore();

            return redirect()->back()->with('success', 'กู้คืนเรียบร้อยแล้ว');
        }

        // if ($action === 'delete') {
        //     Item::withTrashed()
        //         ->whereIn('id', $ids)
        //         ->where('user_id', Auth::id())
        //         ->forceDelete();

        //     return redirect()->back()->with('success', 'ลบถาวรเรียบร้อยแล้ว');
        // }

        if ($action === 'delete') {
            $items = Item::withTrashed()
                ->whereIn('id', $ids)
                ->where('user_id', Auth::id())
                ->get();

            foreach ($items as $item) {
                // ตรวจสอบว่าเป็นไฟล์ไม่ใช่โฟลเดอร์ และมี path จริง
                if ($item->type === 'file' && $item->path) {
                    $filePath = public_path($item->path);
                    if (file_exists($filePath)) {
                        unlink($filePath); // ลบไฟล์จริง
                    }
                }
            }

            // ลบถาวรจากฐานข้อมูล
            Item::withTrashed()
                ->whereIn('id', $ids)
                ->where('user_id', Auth::id())
                ->forceDelete();

            return redirect()->back()->with('success', 'ลบถาวรเรียบร้อยแล้ว');
        }

        return redirect()->back()->with('error', 'การดำเนินการไม่ถูกต้อง');
    }

    public function rename(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $item = Item::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // เช็กว่ามีชื่อซ้ำใน parent เดียวกันไหม
        $exists = Item::where('parent_id', $item->parent_id)
            ->where('user_id', Auth::id())
            ->where('name', $request->name)
            ->where('id', '!=', $item->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'มีชื่อซ้ำกันอยู่แล้ว'], 422);
        }

        $item->name = $request->input('name');
        $item->save();

        return response()->json(['message' => 'เปลี่ยนชื่อเรียบร้อยแล้ว']);
    }

}

