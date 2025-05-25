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

    <!-- Main Content -->
    <main class="p-6">

        <form method="POST" class="" x-data="{ open: false }">
            @csrf
            <div class="relative">
                <input type="hidden" name="parent_id" value="{{ $parentId }}">

                <button type="button" @click="open = !open"
                    class="bg-green-700 hover:bg-green-600 text-white px-4 py-2 rounded transition">
                    +เพิ่ม
                </button>

                <!-- Dropdown menu -->
                <div x-show="open" @click.away="open = false"
                    class="absolute mt-2 w-48 bg-white border rounded shadow-lg z-20">
                    <button type="button" @click="open = false; $dispatch('open-upload-modal')"
                        class="block w-full text-left px-4 py-2 hover:bg-gray-100">อัปโหลดไฟล์</button>
                    <button type="button" @click="open = false; $dispatch('open-create-folder')"
                        class="block w-full text-left px-4 py-2 hover:bg-gray-100">
                        สร้างโฟลเดอร์
                    </button>
                </div>
            </div>
        </form>

        @if (!empty($breadcrumbs))
            <nav class="text-sm text-gray-600 my-4 flex flex-wrap items-center gap-1">
                <a href="{{ route('items.index') }}" class="truncate max-w-[120px] inline-block align-middle"
                    title="หน้าหลัก">หน้าหลัก</a>
                @foreach ($breadcrumbs as $crumb)
                    <span>&nbsp;/&nbsp;</span>
                    <a href="{{ route('items.index', ['parent_id' => $crumb->id]) }}"
                        class="truncate max-w-[120px] inline-block align-middle" title="{{ $crumb->name }}">
                        {{ $crumb->name }}
                    </a>
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

        <div x-data="{ show: false }" x-show="show" @open-create-folder.window="show = true"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="show = false" class="bg-white p-6 rounded shadow-lg max-w-md w-full">
                <h3 class="text-lg font-bold mb-4">สร้างโฟลเดอร์ใหม่</h3>
                <form action="{{ route('items.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="folder">
                    <input type="hidden" name="parent_id" value="{{ $parentId }}">
                    <input type="text" name="name" placeholder="ชื่อโฟลเดอร์"
                        class="w-full border border-gray-300 rounded p-2 mb-4">
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="show = false"
                            class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">ยกเลิก</button>
                        <button type="submit"
                            class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">สร้าง</button>
                    </div>
                </form>
            </div>
        </div>
        <div x-data="{ show: false }" x-show="show" @open-upload-modal.window="show = true"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="show = false" class="bg-white p-6 rounded shadow-lg max-w-md w-full">
                <h3 class="text-lg font-bold mb-4">อัปโหลดไฟล์</h3>

                <form action="{{ route('items.upload') }}" method="POST" enctype="multipart/form-data"
                    id="uploadForm">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $parentId }}">

                    <div id="fileInputContainer"
                        class="border-2 border-dashed border-gray-300 rounded p-6 text-center cursor-pointer hover:border-blue-400"
                        onclick="document.getElementById('fileInput').click();" ondragover="event.preventDefault();"
                        ondrop="handleDrop(event)">
                        <input type="file" name="files[]" id="fileInput" multiple class="hidden"
                            onchange="updateFileList(event)">
                        <p class="text-lg text-gray-700 font-medium">คลิกเพื่อเลือกไฟล์</p>
                        <p class="text-sm text-gray-500">ลากไฟล์มาวางหรือคลิกเพื่อเลือก</p>
                        <p class="text-sm text-gray-500">สูงสุด 1.5GB ต่อครั้ง</p>
                    </div>

                    <ul id="fileList" class="mt-4 text-sm text-gray-700 space-y-1"></ul>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="show = false"
                            class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">ยกเลิก</button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">อัปโหลด</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Context Menu -->
        <div id="context-menu" class="hidden absolute bg-white border border-gray-300 rounded-lg shadow-lg z-50 w-48">
            <ul class="text-sm text-gray-800">
                <li>
                    <button id="download-item" class="px-4 py-2 hover:bg-gray-100 cursor-pointer">📥
                        ดาวน์โหลด</button>
                </li>
                <li id="rename-item" class="px-4 py-2 hover:bg-gray-100 cursor-pointer">✏️ เปลี่ยนชื่อ
                </li>
                <li id="share-item" class="px-4 py-2 hover:bg-gray-100 cursor-pointer">🔗 คัดลอกลิงค์</li>
                <li id="delete-item" class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-red-500">🗑️
                    ย้ายไปที่ถังขยะ</li>
            </ul>
        </div>


    </main>
    <!-- Script -->
    <script>
        // Context menu logic
        // const toggle = document.getElementById('dropdownToggle');
        const menu = document.getElementById('dropdownMenu');

        // document.addEventListener('click', function(e) {
        //     if (toggle.contains(e.target)) {
        //         menu.classList.toggle('hidden');
        //     } else if (!menu.contains(e.target)) {
        //         menu.classList.add('hidden');
        //     }
        // });
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
                    // สำหรับ Desktop: ใช้ตำแหน่งเมาส์
                    top = event.pageY;
                    left = event.pageX;
                } else {
                    // สำหรับ Mobile: ใช้ตำแหน่งของปุ่ม (3 จุด)
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


            document.getElementById('rename-item').addEventListener('click', function() {
                hideContextMenu();
                if (currentItemId) {
                    const currentName = document.querySelector(`[data-id="${currentItemId}"]`).dataset.name;

                    // แยกชื่อกับนามสกุล
                    const lastDotIndex = currentName.lastIndexOf('.');
                    let baseName = currentName;
                    let extension = '';

                    if (lastDotIndex > 0) {
                        baseName = currentName.substring(0, lastDotIndex);
                        extension = currentName.substring(lastDotIndex); // เช่น .pdf
                    }

                    // สร้าง prompt พร้อมชื่อเดิม
                    const newBaseName = prompt("เปลี่ยนชื่อไฟล์:", baseName);

                    if (newBaseName) {
                        const newName = newBaseName + extension;

                        fetch(`/items/${currentItemId}/rename`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    name: newName
                                })
                            }).then(res => {
                                if (res.ok) {
                                    alert('เปลี่ยนชื่อสำเร็จแล้ว');
                                    setTimeout(() => {
                                        location.reload();
                                    }, 1500); // รอให้ toast แสดงก่อนค่อยรีโหลด
                                } else {
                                    alert('ไม่สามารถเปลี่ยนชื่อได้', true);
                                }
                            })
                            .catch(() => {
                                alert('เกิดข้อผิดพลาดขณะเปลี่ยนชื่อ', true);
                            });
                    }
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

            document.getElementById('share-item').addEventListener('click', function() {
                hideContextMenu();
                if (currentItemId) {
                    fetch(`/share/${currentItemId}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(res => {
                            if (!res.ok) throw new Error('การแชร์ล้มเหลว');
                            return res.json();
                        })
                        .then(data => {
                            copyToClipboard(data.link);
                        })
                        .catch(() => {
                            alert('เกิดข้อผิดพลาดในการสร้างลิงก์', true);
                        });
                }
            });



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

            document.getElementById('delete-item').addEventListener('click', function() {
                hideContextMenu();
                if (currentItemId && confirm("คุณแน่ใจหรือไม่ว่าต้องการลบ?")) {
                    fetch(`/items/${currentItemId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(res => location.reload());
                }
            });
        });
    </script>
</body>

</html>
