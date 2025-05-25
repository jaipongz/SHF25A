<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <header class="bg-blue-900 text-white p-4 flex justify-between items-center shadow-md">
        <h1 class="text-xl font-semibold">📁 Share Files Internally</h1>
        <div class="flex items-center gap-4">
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-6">

        

        @if (!empty($breadcrumbs))
            <nav class="text-sm text-gray-600 my-4 flex flex-wrap items-center gap-1">
                @foreach ($breadcrumbs as $crumb)
                    <span>&nbsp;/&nbsp;</span>
                    <p 
                        class="truncate max-w-[120px] inline-block align-middle" title="{{ $crumb->name }}">
                        {{ $crumb->name }}
                    </p>
                @endforeach
            </nav>
        @endif
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mt-10">
            @forelse($items as $item)
                <div class="relative group bg-white p-4 pt-6 rounded shadow hover:shadow-lg transition-all cursor-pointer text-center select-none"
                    data-id="{{ $item['id'] }}" data-name="{{ $item['name'] }}" data-type="{{ $item['type'] }}"
                    @if ($item['type'] === 'folder') ondblclick="window.location='{{ route('items.index', ['parent_id' => $item['id']]) }}'"
                 @else
                 data-url="{{ $item['url'] }}" data-file_type="{{ $item['file_type'] }}" data-extension="{{ $item['extension'] }}" @endif
                    oncontextmenu="showContextMenu(event, {{ $item['id'] }}, '{{ $item['type'] }}', '{{ addslashes($item['name']) }}')">
                    @if ($item['type'] === 'folder')
                        <div class="text-6xl">📁</div>
                    @else
                        @if ($item['file_type'] === 'image')
                            @if (App::environment('production'))
                                <img src="{{ secure_asset('public/' . $item['url']) }}" alt="{{ $item['name'] }}"
                                    class="w-16 h-16 object-cover mx-auto mb-2 rounded pointer-events-none">
                            @else
                                <img src="{{ asset($item['url']) }}" alt="{{ $item['name'] }}"
                                    class="w-16 h-16 object-cover mx-auto mb-2 rounded pointer-events-none">
                            @endif
                        @elseif ($item['file_type'] === 'document')
                            <div class="text-5xl mb-2 pointer-events-none">📄</div>
                        @elseif ($item['file_type'] === 'video')
                            <video class="w-24 h-16 object-cover mx-auto mb-2 rounded pointer-events-none" muted>
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
            @empty
                <p class="text-gray-500">ไม่มีไฟล์หรือโฟลเดอร์</p>
            @endforelse
        </div>

        
        <!-- Context Menu -->
        <div id="context-menu" class="hidden absolute bg-white border border-gray-300 rounded-lg shadow-lg z-50 w-48">
            <ul class="text-sm text-gray-800">
                <li>
                    <button id="download-item" class="px-4 py-2 hover:bg-gray-100 cursor-pointer">📥
                        ดาวน์โหลด</button>
                </li>
            </ul>
        </div>

    </main>

    <!-- Script -->
    <script>
        const menu = document.getElementById('dropdownMenu');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');

        function updateFileList(event) {
            const files = event.target.files || event.dataTransfer.files;
            fileList.innerHTML = ''; // clear old list

            Array.from(files).forEach(file => {
                const li = document.createElement('li');
                li.textContent = file.name;
                fileList.appendChild(li);
            });
        }

        function handleDrop(event) {
            event.preventDefault();
            const dt = event.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            updateFileList({
                target: {
                    files
                }
            });
        }

        function toggleSelection() {
            const checkboxes = document.querySelectorAll('.checkbox-item');
            const selected = Array.from(checkboxes).filter(cb => cb.checked);
            const actionBar = document.getElementById('action-bar');
            const count = document.getElementById('selected-count');

            if (selected.length > 0) {
                actionBar.classList.remove('hidden');
                count.textContent = selected.length;
            } else {
                actionBar.classList.add('hidden');
            }
        }

        function cancelSelection() {
            const checkboxes = document.querySelectorAll('.checkbox-item');
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('action-bar').classList.add('hidden');
        }

        function deleteSelected() {
            const selectedIds = Array.from(document.querySelectorAll('.checkbox-item:checked')).map(cb => cb.value);

            if (selectedIds.length === 0) return;

            if (!confirm(`คุณต้องการลบ ${selectedIds.length} รายการใช่หรือไม่?`)) return;

            fetch('{{ route('items.bulkDelete') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ids: selectedIds
                }),
            }).then(response => {
                if (response.ok) {
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาดในการลบ');
                }
            });
        }



        @if (session('success'))
            document.addEventListener('DOMContentLoaded', function() {
                alert('อัปโหลดเสร็จสิ้น');
            });
        @endif

        function showContextMenu(event, element) {
            event.preventDefault();
            currentCard = element;
            const menu = document.getElementById("context-menu");
            menu.style.top = `${event.pageY}px`;
            menu.style.left = `${event.pageX}px`;
            menu.classList.remove("hidden");
        }

        document.addEventListener("click", function(event) {
            const menu = document.getElementById("context-menu");
            if (!menu.contains(event.target)) {
                menu.classList.add("hidden");
            }
        });

        function handleMenu(action) {
            const name = currentCard?.querySelector("p")?.innerText;
            const type = currentCard?.dataset.type;

            switch (action) {
                case 'download':
                    alert(`📥 ดาวน์โหลด${type === 'folder' ? 'โฟลเดอร์' : 'ไฟล์'}: ${name}`);
                    break;
                case 'rename':
                    const newName = prompt("กรอกชื่อใหม่:", name);
                    if (newName) currentCard.querySelector("p").innerText = newName;
                    break;
                case 'copy':
                    const dummyLink = `https://example.com/${type}/${encodeURIComponent(name)}`;
                    navigator.clipboard.writeText(dummyLink);
                    alert("🔗 คัดลอกลิงค์เรียบร้อย!");
                    break;
                case 'delete':
                    const confirmed = confirm(`คุณแน่ใจหรือไม่ว่าจะลบ "${name}"?`);
                    if (confirmed) currentCard.remove();
                    break;
            }

            document.getElementById("context-menu").classList.add("hidden");
        }

        document.addEventListener('DOMContentLoaded', function() {
            const contextMenu = document.getElementById('context-menu');
            let currentItemId = null;
            let itemType = null;

            window.showContextMenu = function(event, itemId) {
                event.preventDefault();
                currentItemId = itemId;
                const itemEl = document.querySelector(`[data-id="${currentItemId}"]`);
                itemType = itemEl?.dataset?.type;

                let top, left;

                if (event.type === 'contextmenu') {
                    top = event.pageY;
                    left = event.pageX;
                } else {
                    const rect = event.target.getBoundingClientRect();
                    top = rect.bottom + window.scrollY;
                    left = rect.left + window.scrollX;
                }

                contextMenu.style.top = `${top}px`;
                contextMenu.style.left = `${left}px`;
                contextMenu.classList.remove('hidden');
            }

            window.hideContextMenu = function() {
                contextMenu.classList.add('hidden');
            }

            document.addEventListener('click', function(e) {
                if (!contextMenu.contains(e.target)) {
                    hideContextMenu();
                }
            });


            function copyToClipboard(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    // Modern API
                    navigator.clipboard.writeText(text)
                        .then(() => alert('คัดลอกลิงก์แล้ว'))
                        .catch(() => alert('คัดลอกลิงก์ไม่สำเร็จ', true));
                } else {
                    // Fallback method
                    const textArea = document.createElement("textarea");
                    textArea.value = text;
                    textArea.style.position = "fixed"; // ไม่ให้ scroll ไปยัง element
                    textArea.style.left = "-999999px";
                    textArea.style.opacity = 0;
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();

                    try {
                        const successful = document.execCommand('copy');
                        alert(successful ? 'คัดลอกลิงก์แล้ว' : 'คัดลอกลิงก์ไม่สำเร็จ', !successful);
                    } catch (err) {
                        alert(err);
                        alert('คัดลอกลิงก์ไม่สำเร็จ', true);
                    }

                    document.body.removeChild(textArea);
                }
            }


            document.getElementById('download-item').addEventListener('click', function() {
                hideContextMenu();
                if (currentItemId) {
                    const itemEl = document.querySelector(`[data-id="${currentItemId}"]`);
                    let itemURL = itemEl?.dataset?.url;
                    const itemType = itemEl?.dataset.type;
                    if (itemType != "folder") {
                        const fileName = itemEl?.dataset?.name;
                        if (!itemURL.startsWith('/public') && window.location.hostname !== '127.0.0.1') {
                            itemURL = 'public/' + itemURL;
                        }
                        const fullURL = window.location.origin + '/' + itemURL;
                        if (itemURL) {
                            const a = document.createElement('a');
                            a.href = fullURL;
                            a.download = fileName || 'download'; // fallback filename
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            alert('ดาวน์โหลดแล้ว');
                        } else {
                            alert('เกิดข้อผิดพลาดในการดาวน์โหลด', true);
                        }
                    } else {
                        alert('ไม่สามารถดาวน์โหลดโฟลเดอร์ได้', true);
                    }

                }
            });
        });
    </script>
</body>

</html>
