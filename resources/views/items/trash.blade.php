<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FM Folder - Trash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .menu-popup {
            display: none;
            position: absolute;
            z-index: 50;
        }

        .file-item {
            background-color: #ffffff;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col items-center">
    <header class="bg-blue-900 text-white p-4 w-full flex justify-between items-center shadow-md"">
        <h1 class="text-xl font-semibold">🗑️ Recycle Bin</h1>
        <div class="flex items-center gap-4">
            <a href="{{ route('items.index') }}" class="text-red-300 text-2xl hover:text-red-500" title="File">📁</a>
            <a href="{{ route('items.trash') }}" class="text-red-300 text-2xl hover:text-red-500" title="Trash">🗑️</a>
            <form method="POST" action="{{ route('logout') }}" class="w-full mt-4">
                @csrf
                <button class="flex items-center gap-2 hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7" />
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    </header>

    <!-- Trash Section -->
    <main class="p-6 w-full max-w-3xl">
        <div class="space-y-4" id="trashList">
            <form id="bulkActionForm" method="POST" action="{{ route('items.bulkActionTrash') }}">
                @csrf
                <input type="hidden" name="action" id="bulkActionType">
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mt-4">
                    @foreach ($items as $item)
                        <div class="relative group bg-white p-4 pt-6 rounded shadow hover:shadow-lg transition-all cursor-pointer text-center select-none"
                            data-id="{{ $item['id'] }}" data-name="{{ $item['name'] }}"
                            data-type="{{ $item['type'] }}"
                            @if ($item['type'] === 'folder') ondblclick="window.location='{{ route('shared.index', ['parent_id' => $item['id']]) }}'"
                 @else
                 data-url="{{ $item['url'] }}" data-file_type="{{ $item['file_type'] }}" data-extension="{{ $item['extension'] }}" @endif>
                            <input type="checkbox" class="absolute top-2 left-2 item-checkbox" name="ids[]"
                                value="{{ $item['id'] }}" onchange="updateSelected()">
                            <div class="lg:hidden absolute top-2 right-2 z-10">
                                <button onclick="event.stopPropagation();showContextMenu(event, {{ $item['id'] }})"
                                    class="text-gray-600 hover:text-gray-900 text-xl px-2 rounded">
                                    &#8942;
                                </button>
                            </div>
                            @if ($item['type'] === 'folder')
                                @if (App::environment('production'))
                                    <img src="{{ secure_asset('public/assets/img/folder.png') }}" alt="Folder Icon"
                                        class="w-16 h-16 mx-auto mb-2 pointer-events-none">
                                @else
                                    <img src="{{ asset('assets/img/folder.png') }}" alt="Folder Icon"
                                        class="w-16 h-16 mx-auto mb-2 pointer-events-none">
                                @endif
                            @else
                                @if ($item['file_type'] === 'image')
                                    @if (App::environment('production'))
                                        <img src="{{ secure_asset('public/' . $item['url']) }}"
                                            alt="{{ $item['name'] }}"
                                            class="w-16 h-16 object-cover mx-auto mb-2 rounded pointer-events-none">
                                    @else
                                        <img src="{{ asset($item['url']) }}" alt="{{ $item['name'] }}"
                                            class="w-16 h-16 object-cover mx-auto mb-2 rounded pointer-events-none">
                                    @endif
                                @elseif ($item['file_type'] === 'document')
                                    <div class="text-5xl mb-2 pointer-events-none">📄</div>
                                @elseif ($item['file_type'] === 'video')
                                    <video class="w-24 h-16 object-cover mx-auto mb-2 rounded pointer-events-none"
                                        muted>
                                        <source
                                            src="{{ App::environment('production') ? asset('public/' . $item['path']) : asset($item['path']) }}"
                                            type="video/{{ $item['extension'] }}">
                                        Your browser does not support the video tag.
                                    </video>
                                @else
                                    <div class="text-5xl mb-2 pointer-events-none">📁</div>
                                @endif
                            @endif

                            {{-- FILE/FOLDER NAME --}}
                            <div class="text-base font-medium truncate pointer-events-none">{{ $item['name'] }}</div>
                        </div>
                    @endforeach
                </div>
            </form>
        </div>
    </main>
    <div id="actionBar"
        class="hidden fixed top-0 left-0 right-0 bg-white shadow p-4 z-50 flex justify-between items-center">
        <span class="font-semibold">เลือกแล้ว <span id="selectedCount">0</span> รายการ</span>
        <div class="space-x-2">
            <button onclick="clearSelection()" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">ยกเลิก</button>
            <button onclick="submitBulkAction('delete')"
                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">ลบถาวร</button>
            <button onclick="submitBulkAction('restore')"
                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">กู้คืน</button>
        </div>
    </div>

    <!-- Context Menu -->
    <div id="fileMenu" class="menu-popup bg-white border border-gray-300 rounded shadow-lg w-48">
        <ul class="text-sm text-gray-800">
            <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer" onclick="handleMenu('restore')">🔄 กู้คืน</li>
            <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer" onclick="handleMenu('open')">📂 เปิดไฟล์</li>
            <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-red-500" onclick="handleMenu('delete')">🗑️ ลบ
            </li>
        </ul>
    </div>


    <script>
        // Show custom menu
        function showMenu(event, button) {
            event.stopPropagation();
            const menu = document.getElementById("fileMenu");
            const rect = button.getBoundingClientRect();
            menu.style.top = `${rect.bottom + window.scrollY}px`;
            menu.style.left = `${rect.left + window.scrollX}px`;
            menu.style.display = "block";
            selectedFileElement = button.closest(".file-item");
        }

        // Hide menu on click anywhere
        document.addEventListener("click", () => {
            document.getElementById("fileMenu").style.display = "none";
        });

        // Menu action handler
        function handleMenu(action) {
            const filenameElement = selectedFileElement?.querySelector("p");
            const filename = filenameElement?.innerText;

            switch (action) {
                case "restore":
                    alert(`🔄 กู้คืนไฟล์: ${filename}`);
                    break;
                case "open":
                    alert(`📂 เปิดไฟล์: ${filename}`);
                    break;
                case "delete":
                    if (confirm(`คุณต้องการลบไฟล์ "${filename}" ถาวรหรือไม่?`)) {
                        selectedFileElement.remove();
                    }
                    break;
            }

            document.getElementById("fileMenu").style.display = "none";
        }

        function updateSelected() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const selected = Array.from(checkboxes).filter(cb => cb.checked);
            const actionBar = document.getElementById('actionBar');
            const countSpan = document.getElementById('selectedCount');

            if (selected.length > 0) {
                actionBar.classList.remove('hidden');
            } else {
                actionBar.classList.add('hidden');
            }

            countSpan.textContent = selected.length;
        }

        function submitBulkAction(type) {
            document.getElementById('bulkActionType').value = type;
            document.getElementById('bulkActionForm').submit();
        }

        function clearSelection() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            updateSelected();
        }

        function toggleSelectAllFromTop() {
            const isChecked = document.getElementById('selectAllTop').checked;
            const checkboxes = document.querySelectorAll('.item-checkbox');

            checkboxes.forEach(cb => cb.checked = isChecked);
            updateSelected();
        }
    </script>

</body>

</html>
