        @extends('layouts.app')
        @section('title','Inventory')
        @section('content')

     

        <style>
    .inv-wrapper {
    background:#f8fafc;
    padding: 1rem 0 1rem;
}
        .inv-kpi {
            background:#fff;
            border-radius:14px;
            padding:1.25rem;
            box-shadow:0 6px 18px rgba(0,0,0,.06);
        }
        .inv-kpi-label {
            font-size:.75rem;
            text-transform:uppercase;
            color:#6c757d;
        }
        .inv-kpi-value {
            font-size:1.9rem;
            font-weight:800;
            color:#fd7e14;
        }
        .inv-card {
            background:#fff;
            border-radius:14px;
            padding:1.4rem;
            box-shadow:0 6px 18px rgba(0,0,0,.05);
        }
   .chart-wrapper {
    height: 260px;
}
   .inv-card canvas {
            height:100% !important;
        }
        .stock-link {
            color:#fd7e14;
            font-weight:600;
            cursor:pointer;
        }
        .stock-link:hover {
            text-decoration:underline;
        }
        .search-box {
            position:relative;
            width:320px;
        }
        .search-suggestions {
            position:absolute;
            top:100%;
            left:0;
            right:0;
            background:#fff;
            z-index:1000;
            border-radius:8px;
            box-shadow:0 6px 18px rgba(0,0,0,.1);
            display:none;
        }
        .search-suggestions div {
            padding:8px 12px;
            cursor:pointer;
        }
        .search-suggestions div:hover {
            background:#f1f3f5;
        }

        /* ===== PATCH: Equal height cards ===== */
        .inv-equal-row {
            display:flex;
            align-items:stretch;
        }
        .inv-equal-row > [class*="col-"] {
            display:flex;
        }
        .inv-equal-row .inv-card {
            flex:1;
            display:flex;
            flex-direction:column;
        }

       .stock-health-body {
    flex:1;
    display:flex;
    justify-content:center;
    align-items:center;
    gap:3.5rem;
    padding: 1rem 0;
}

.stock-health-body .chart-wrapper {
    height:180px;
    width:180px;
}
        .stock-health-info {
            display:flex;
            flex-direction:column;
            gap:.35rem;
            align-items:center;
            text-align:center;
        }

        .stock-health-info .inv-kpi-value {
            font-size:2.2rem;
            line-height:1;
        }
        .stock-health-footer {
            margin-top:1.25rem;
            padding-top:1rem;
            border-top:1px solid #edf0f2;
            display:flex;
            justify-content:space-between;
        }
        .stock-health-footer div {
            text-align:left;
        }
        .stock-health-footer div:last-child {
            text-align:right;
        }

        .stock-health-footer strong {
            font-size:1.1rem;
            font-weight:700;
        }
        .stock-health-legend {
            display:flex;
            gap:1.25rem;
            margin-top:.75rem;
            justify-content:center;
        }


        .stock-health-legend div {
            display:flex;
            align-items:center;
            gap:.4rem;
            font-size:.85rem;
            color:#495057;
        }

        .stock-health-legend .dot {
            width:10px;
            height:10px;
            border-radius:50%;
            display:inline-block;
        }

        .stock-health-legend .dot.in {
            background:#fd7e14;
        }

        .stock-health-legend .dot.out {
            background:#ffd8b0;
        }
        /* ===== PATCH: Warehouse card alignment & polish ===== */
        .warehouse-card {
            display: flex;
            flex-direction: column;
        }

        .warehouse-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .warehouse-body .chart-wrapper {
            width: 100%;
            height: 200px;
        }

        /* ===== PATCH: Scrollable Aging Stock Modal ===== */

        /* Lock modal height */
        #itemModal .modal-dialog {
            max-width: 900px;            /* optional, keep */
            max-height: 90vh;
        }

        /* Make modal content a flex container */
        #itemModal .modal-content {
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        /* Header stays fixed */
        #itemModal .modal-header {
            flex-shrink: 0;
        }

        /* 🔥 THIS IS THE KEY PART */
        #itemModal .modal-body {
            overflow-y: auto;
            max-height: calc(90vh - 120px); /* header + padding */
        }


        /* ===== PATCH: Warehouse labels typography ===== */
        .chart-wrapper canvas {
            font-family:Inter,system-ui,-apple-system,sans-serif;
        }

    .aging-stock-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #6c757d;
        letter-spacing: .03em;
        padding: 12px 14px;
    }

    .aging-stock-table td {
        padding: 14px;
        vertical-align: middle;
    }

    .aging-stock-table .aging-code {
        width: 120px;
    }

    .aging-stock-table .aging-code .stock-link {
        color: #fd7e14;
        font-weight: 700;
        text-decoration: none;
    }

    .aging-stock-table .aging-code .stock-link:hover {
        text-decoration: underline;
    }

    .aging-stock-table .aging-desc {
        max-width: 420px;
    }

    .aging-stock-table .aging-desc .fw-semibold {
        font-size: .95rem;
    }

    .aging-stock-table .aging-desc .text-muted {
        font-size: .8rem;
    }

    .aging-stock-table td.text-end {
        white-space: nowrap;
        font-weight: 600;
    }

    .stock-health-click {
        cursor: pointer;
    }

    .stock-health-click:hover strong {
        color: #fd7e14;
        text-decoration: underline;
    }
    .aging-sort {
        color: #495057;
        font-weight: 600;
        text-decoration: none;
    }

    .aging-sort:hover {
        color: #fd7e14;
    }

    .aging-sort span {
        margin-left: 4px;
    }
    /* ===== Stock Details Toolbar ===== */
    .aging-filters .btn {
        border-radius: 999px;
        padding: 4px 12px;
        font-weight: 500;
    }

    .aging-filters .btn.active {
        background: #fd7e14;
        border-color: #fd7e14;
        color: #fff;
    }

    .modal-body .btn-outline-warning {
        border-radius: 999px;
    }
    .inventory-sort {
    color:#495057;
    font-weight:600;
    text-decoration:none;
}

.inventory-sort:hover {
    color:#fd7e14;
}

.inventory-sort.asc span::after {
    content: " ▲";
}

.inventory-sort.desc span::after {
    content: " ▼";
}

/* ===== Inventory Distribution polish ===== */
.dist-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6c757d;
    margin-bottom: 0.25rem;
    letter-spacing: 0.04em;
}

.dist-chart {
    height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}

        </style>

        <div class="inv-wrapper">

        {{-- ================= KPIs ================= --}}
        <div class="row g-4 mb-4">
    @foreach([
        ['Inventory Value',$inventoryTotal,'Total stock valuation', true],
        ['Inventory Aging', $agingValue, 'Slow-moving inventory (90+ days)', true],
        ['Reorder Alerts',$reorderCount,'Items below threshold', false],
        ['Total SKUs',$totalItems,'Active products', false]
    ] as $kpi)
            <div class="col-lg-3 col-md-6">
    <div class="inv-kpi
        {{ $kpi[0] === 'Inventory Aging' ? 'aging-stock-trigger' : '' }}
        {{ $kpi[0] === 'Reorder Alerts' ? 'reorder-alerts-trigger' : '' }}"
        style="{{ $kpi[0] === 'Inventory Aging' || $kpi[0] === 'Reorder Alerts' ? 'cursor:pointer' : '' }}">




                    <div class="inv-kpi-label">{{ $kpi[0] }}</div>
                    <div class="inv-kpi-value"
            title="₱{{ is_numeric($kpi[1]) ? number_format($kpi[1],2) : $kpi[1] }}">
        @php
        $v = $kpi[1];
        $isMoney = $kpi[3];

        if ($isMoney) {
            if ($v >= 1_000_000_000) {
                echo '₱' . number_format($v / 1_000_000_000, 1) . 'B';
            } elseif ($v >= 1_000_000) {
                echo '₱' . number_format($v / 1_000_000, 1) . 'M';
            } elseif ($v >= 1_000) {
                echo '₱' . number_format($v / 1_000, 1) . 'K';
            } else {
                echo '₱' . number_format($v, 2);
            }
        } else {
            echo number_format($v);
        }
    @endphp

        </div>

                    <small>{{ $kpi[2] }}</small>
                </div>
            </div>
        @endforeach
        </div>

        {{-- ================= INVENTORY TREND ================= --}}
        <div class="row g-4 mb-4">
    <div class="col-lg-12">
        <div class="inv-card">
            <div class="d-flex justify-content-between mb-3">
                <h6 class="mb-0">Inventory Value Trend</h6>
                <select id="yearSelect" class="form-select form-select-sm" style="width:120px">
                    @for($y=now()->year;$y>=now()->year-5;$y--)
                        <option value="{{ $y }}" {{ request('year',now()->year)==$y?'selected':'' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="chart-wrapper">
                <canvas id="inventoryTrend"></canvas>
            </div>
              </div>
    </div>
</div>

        {{-- ================= STOCK HEALTH + WAREHOUSE ================= --}}
        <div class="row g-4 mb-1 inv-equal-row">

        {{-- STOCK HEALTH --}}
        <div class="col-lg-6">
            <div class="inv-card">
                <h6 class="mb-3">Stock Health</h6>

                <div class="stock-health-body">
                    <div class="text-center">
                        <div class="chart-wrapper">
                            <canvas id="stockHealth"></canvas>
                        </div>
                    </div>

            <div class="stock-health-info">
        <div class="inv-kpi-label">Stock Availability</div>

        <div class="inv-kpi-value">
            {{ number_format($availabilityRate,1) }}%
        </div>

        <small>SKUs currently available</small>

        {{-- ✅ ADD THIS LEGEND --}}
        <div class="stock-health-legend">
            <div>
                <span class="dot in"></span>
                <span>Stock on Hand</span>
            </div>
            <div>
                <span class="dot out"></span>
                <span>Out of Stock</span>
            </div>
        </div>
    </div>


                </div>
    <div class="stock-health-footer">
        <div class="stock-health-click" data-type="in">
            <div class="inv-kpi-label">Stock on Hand</div>
            <strong>{{ number_format($inStock) }}</strong>
        </div>

        <div class="stock-health-click text-end" data-type="out">
            <div class="inv-kpi-label">Out of Stock</div>
            <strong>{{ number_format($outOfStock) }}</strong>
        </div>
    </div>

            </div>
        </div>

     <div class="col-lg-6">
    <div class="inv-card">
        <h6 class="mb-3">Inventory Distribution</h6>

   <div class="row text-center align-items-center">
    <div class="col-md-6">
        <div class="dist-title">By Warehouse</div>
        <div class="chart-wrapper dist-chart">
            <canvas id="warehousePie"></canvas>
        </div>
    </div>

    <div class="col-md-6">
        <div class="dist-title">By Division</div>
        <div class="chart-wrapper dist-chart">
            <canvas id="divisionPie"></canvas>
        </div>
    </div>
</div>



</div>

        </div>
    </div>
</div>





        {{-- ================= INVENTORY TABLE ================= --}}
<div class="row g-2 mt-0">
    <div class="col-lg-12">
        <div class="inv-card">
            <div class="d-flex justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
        <h6 class="mb-0">Inventory</h6>

    <select
        class="form-select form-select-sm"
        style="width:180px"
        onchange="
            const year = '{{ request('year') }}';
            let url = this.value;
            if (year) {
                url += '&year=' + encodeURIComponent(year);
            }
            location.href = url;
        ">

            <option value="{{ route('inventory.index') }}">
                All Divisions
            </option>

            @foreach($divisions as $div)
                <option
                    value="{{ route('inventory.index', ['division' => $div]) }}"
                    {{ request('division') === $div ? 'selected' : '' }}>
                    {{ $div }}
                </option>
            @endforeach
        </select>
    </div>

                <div class="d-flex gap-2">
        <div class="search-box position-relative">
            <input
                id="inventorySearch"
                class="form-control form-control-sm pe-5"
                placeholder="Search stock, description, category"
                autocomplete="off"
            >
            <span id="clearSearch"
                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                        cursor:pointer;display:none;color:#adb5bd;">
                ✕
            </span>
        </div>


                 <a href="{{ route('inventory.export', request()->only(
    'division',
    'year',
    'search',
    'sort',
    'dir'
)) }}"
   class="btn btn-outline-warning btn-sm">

                        <i class="bi bi-file-earmark-excel"></i> Export
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Stock Code</th>
                 <th>Long Description</th>
<th>UOM</th>
                            <th>Division</th>
                            <th>Category</th>
                       <th class="text-end">
    <a href="#" class="inventory-sort" data-sort="qty">
        Qty <span></span>
    </a>
</th>

<th class="text-end">
    <a href="#" class="inventory-sort" data-sort="value">
        Value <span></span>
    </a>
</th>

                        </tr>
                    </thead>
                    <tbody id="inventoryBody">
                @include('inventory.partials.rows', ['inventoryRows' => $inventoryRows])

                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $inventoryRows->links('pagination::bootstrap-5') }}
            </div>
        </div>

        </div>

        {{-- ================= MODAL ================= --}}
        <div class="modal fade" id="itemModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="itemModalTitle">Stock Details</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="modalContent">
                        <div class="text-center text-muted py-5">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- ================= REORDER ALERTS MODAL ================= --}}
    <div class="modal fade" id="reorderModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reorder Alerts</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reorderModalContent">
                    <div class="text-center text-muted py-5">
                        Loading reorder alerts...
                    </div>
                </div>
            </div>
        </div>
    </div>


    @push('scripts')
    <script>
        Chart.register(ChartDataLabels);
    window.agingState = {
        bucket: null,
        sort: 'value',
        dir: 'desc',
        original: true
    };
    window.inventoryState = {
    sort: '{{ request('sort', 'value') }}',
    dir: '{{ request('dir', 'desc') }}',
    original: true
};


    document.addEventListener('DOMContentLoaded', () => {

          document.querySelectorAll('.inventory-sort').forEach(a => {
        if (a.dataset.sort === inventoryState.sort) {
            a.classList.add(inventoryState.dir);
        }
    });
        /* ================= MODAL ================= */
        const modalEl = document.getElementById('itemModal');
        const modalContent = document.getElementById('modalContent');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            modalEl.addEventListener('hidden.bs.modal', () => {
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            
            document.getElementById('itemModalTitle').innerText = 'Stock Details';

            modalContent.innerHTML =
                '<div class="text-center text-muted py-5">Loading...</div>';
        });




    function openItemModal(url, loadingText, title = 'Stock Details') {
        // 🔹 Set modal title
        document.getElementById('itemModalTitle').innerText = title;

        modalContent.innerHTML =
            `<div class="text-center text-muted py-5">${loadingText}</div>`;

        modal.show();

        fetch(url)
            .then(r => r.text())
            .then(html => modalContent.innerHTML = html);
    }




        document.querySelector('.aging-stock-trigger')
            ?.addEventListener('click', () => {
              const division = '{{ request('division') }}';

let url = `{{ url('/inventory/ajax-aging-stock') }}`;
if (division) {
    url += `?division=${encodeURIComponent(division)}`;
}

openItemModal(
    url,
    'Loading aging stock...'
);

            });

            document.querySelectorAll('.stock-health-click')
        .forEach(el => {
            el.addEventListener('click', () => {
                const type = el.dataset.type;

          const division = '{{ request('division') }}';

let url = `{{ url('/inventory/ajax-stock-status') }}?type=${type}`;
if (division) {
    url += `&division=${encodeURIComponent(division)}`;
}

openItemModal(
    url,
    type === 'in'
        ? 'Loading in-stock items...'
        : 'Loading out-of-stock items...',
    type === 'in'
        ? 'In Stock Items'
        : 'Out of Stock Items'
);

            });
        });
    /* ================= REORDER ALERTS MODAL ================= */
    const reorderModalEl = document.getElementById('reorderModal');
    const reorderModalContent = document.getElementById('reorderModalContent');
    const reorderModal = new bootstrap.Modal(reorderModalEl);


    document.querySelector('.reorder-alerts-trigger')
        ?.addEventListener('click', () => {

            // Close item modal if it’s open
            bootstrap.Modal.getInstance(
                document.getElementById('itemModal')
            )?.hide();

            reorderModalContent.innerHTML =
                '<div class="text-center text-muted py-5">Loading reorder alerts...</div>';

            reorderModal.show();

           const division = '{{ request('division') }}';

let url = `{{ url('/inventory/ajax-reorder-alerts') }}`;
if (division) {
    url += `?division=${encodeURIComponent(division)}`;
}

fetch(url)
    .then(r => r.text())
    .then(html => reorderModalContent.innerHTML = html);
        });

    document
        .getElementById('reorderModal')
        .addEventListener('click', e => {

            const stock = e.target.closest('.stock-link');
            if (!stock) return;

            e.preventDefault();

            // 1️⃣ Close Reorder Alerts modal
            reorderModal.hide();

            // 2️⃣ Open Stock Details modal AFTER close animation
            setTimeout(() => {
                openItemModal(
                    `{{ url('/inventory/ajax-detail') }}/${stock.dataset.code}`,
                    'Loading stock details...',
                    'Stock Details'
                );
            }, 300); // Bootstrap modal transition time
        });

    document.addEventListener('click', e => {

        // ❌ Ignore Reorder Alerts modal entirely
        if (e.target.closest('#reorderModal')) return;

        // ❌ Ignore pagination clicks
        if (e.target.closest('.pagination')) return;

        const stock = e.target.closest('.stock-link');
        if (!stock) return;

        e.preventDefault();

        openItemModal(
            `{{ url('/inventory/ajax-detail') }}/${stock.dataset.code}`,
            'Loading stock details...',
            'Stock Details'
        );
    });

    /* ================= AGING MODAL PAGINATION FIX ================= */
    document.addEventListener('click', e => {

        const link = e.target.closest('.aging-pagination a');
        if (!link) return;

        e.preventDefault(); // ⛔ stop page navigation

        const url = link.getAttribute('href');

        // Show loading INSIDE modal
        modalContent.innerHTML =
            '<div class="text-center text-muted py-5">Loading aging stock...</div>';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.text())
            .then(html => {
                modalContent.innerHTML = html;
            });
    });

    /* ================= REORDER ALERTS PAGINATION FIX ================= */
document.addEventListener('click', e => {

    const link = e.target.closest('#reorderModal .pagination a');
    if (!link) return;

    e.preventDefault();

    const url = link.getAttribute('href');

    // Show loading INSIDE reorder modal
    document.getElementById('reorderModalContent').innerHTML =
        '<div class="text-center text-muted py-5">Loading...</div>';

    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(r => r.text())
        .then(html => {
            document.getElementById('reorderModalContent').innerHTML = html;
        });
});
/* ================= STOCK STATUS (IN/OUT OF STOCK) PAGINATION FIX ================= */
document.addEventListener('click', e => {

    const link = e.target.closest('#itemModal .pagination a');
    if (!link) return;

    e.preventDefault();

    const url = link.getAttribute('href');

    // Show loading INSIDE modal
    document.getElementById('modalContent').innerHTML =
        '<div class="text-center text-muted py-5">Loading...</div>';

    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalContent').innerHTML = html;
        })
        .catch(err => {
            console.error('Pagination error:', err);
            document.getElementById('modalContent').innerHTML =
                '<div class="text-center text-danger py-5">Error loading data</div>';
        });
});

    /* ================= AGING FILTER BUTTONS ================= */
    document.addEventListener('click', e => {

        const btn = e.target.closest('.aging-filter-btn');
        if (!btn) return;

        agingState.bucket = btn.dataset.bucket; // ✅ FIX
        agingState.sort = 'value';
        agingState.dir = 'desc';

        modalContent.innerHTML =
            '<div class="text-center text-muted py-5">Filtering aging stock...</div>';

       const division = '{{ request('division') }}';

let url = `{{ url('/inventory/ajax-aging-stock') }}?bucket=${encodeURIComponent(agingState.bucket)}&sort=${agingState.sort}&dir=${agingState.dir}`;
if (division) {
    url += `&division=${encodeURIComponent(division)}`;
}

fetch(url, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
})

        .then(r => r.text())
        .then(html => modalContent.innerHTML = html);
    });
    /* ================= AGING SORT ================= */
    document.addEventListener('click', e => {

        const sortBtn = e.target.closest('.aging-sort');
        if (!sortBtn) return;

        e.preventDefault();

        const sort = sortBtn.dataset.sort;

        if (agingState.sort !== sort) {
            agingState.sort = sort;
            agingState.dir = 'asc';
            agingState.original = false;
        } else if (agingState.dir === 'asc') {
            agingState.dir = 'desc';
            agingState.original = false;
        } else if (agingState.dir === 'desc') {
            agingState.sort = 'value';
            agingState.dir = 'desc';
            agingState.original = true;
        }


        modalContent.innerHTML =
            '<div class="text-center text-muted py-5">Sorting...</div>';

       const division = '{{ request('division') }}';

let url = `{{ url('/inventory/ajax-aging-stock') }}?bucket=${encodeURIComponent(agingState.bucket ?? '')}&sort=${agingState.sort}&dir=${agingState.dir}`;
if (division) {
    url += `&division=${encodeURIComponent(division)}`;
}

fetch(url, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
})

        .then(r => r.text())
        .then(html => modalContent.innerHTML = html);
    });

    /* ================= INVENTORY SEARCH ================= */
const searchInput = document.getElementById('inventorySearch');
const clearBtn = document.getElementById('clearSearch');

if (searchInput) {
    searchInput.addEventListener('keyup', function () {
        const value = this.value.trim();
        const division = '{{ request('division') }}';
        const year = '{{ request('year') }}';

        let url = `{{ route('inventory.index') }}?search=${encodeURIComponent(value)}`;

// ✅ PRESERVE SORT STATE
url += `&sort=${inventoryState.sort}`;
url += `&dir=${inventoryState.dir}`;

        if (division) url += `&division=${encodeURIComponent(division)}`;
        if (year) url += `&year=${encodeURIComponent(year)}`;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
        
        document.getElementById('inventoryBody').innerHTML = html;

            // ✅ PRESERVE CURRENT DESCRIPTION MODE
            const activeMode =
                document.querySelector('.desc-toggle .btn.active')?.dataset.mode || 'short';

            document.querySelectorAll('.desc-short')
                .forEach(el => el.classList.toggle('d-none', activeMode === 'long'));

            document.querySelectorAll('.desc-long')
                .forEach(el => el.classList.toggle('d-none', activeMode === 'short'));

                    // 🔥 ADD THIS EXACTLY HERE
    document.querySelectorAll('.inventory-sort')
        .forEach(a => a.classList.remove('asc','desc'));

    document.querySelectorAll('.inventory-sort').forEach(a => {
        if (a.dataset.sort === inventoryState.sort) {
            a.classList.add(inventoryState.dir);
        }
    });

        });

        // ✅ Clear button visibility
        if (clearBtn) {
            clearBtn.style.display = value ? 'block' : 'none';
        }
    });
}

if (clearBtn) {
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        clearBtn.style.display = 'none';
        searchInput.dispatchEvent(new Event('keyup'));
    });
}
document.addEventListener('click', e => {

    const exportLink = e.target.closest('a[href*="inventory/export"]');
    if (exportLink) {
        return;
    }

    const sortBtn = e.target.closest('.inventory-sort');
    if (sortBtn) {
        e.preventDefault();

        const sort = sortBtn.dataset.sort;

        if (inventoryState.sort !== sort) {
            inventoryState.sort = sort;
            inventoryState.dir = 'asc';
        } else if (inventoryState.dir === 'asc') {
            inventoryState.dir = 'desc';
        } else {
            inventoryState.sort = 'value';
            inventoryState.dir = 'desc';
        }

        document.querySelectorAll('.inventory-sort')
            .forEach(a => a.classList.remove('asc','desc'));

        sortBtn.classList.add(inventoryState.dir);

        const params = new URLSearchParams({
            search: '{{ request('search') }}',
            division: '{{ request('division') }}',
            year: '{{ request('year') }}',
            sort: inventoryState.sort,
            dir: inventoryState.dir
        });

        fetch(`{{ route('inventory.index') }}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('inventoryBody').innerHTML = html;
        });

        return;
    }

    const pageLink = e.target.closest('.pagination a');
    if (pageLink) {
        // ✅ IGNORE pagination clicks inside modals
        if (pageLink.closest('#itemModal') || pageLink.closest('#reorderModal')) {
            return; // Let the modal-specific handlers deal with it
        }

        e.preventDefault();

        fetch(pageLink.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('inventoryBody').innerHTML = html;
        });
    }
});



        /* ================= DESCRIPTION TOGGLE ================= */
        const descButtons = document.querySelectorAll('.desc-toggle button');

        function applyDescMode(mode = 'short') {
            document.querySelectorAll('.desc-short')
                .forEach(el => el.classList.toggle('d-none', mode === 'long'));
            document.querySelectorAll('.desc-long')
                .forEach(el => el.classList.toggle('d-none', mode === 'short'));
            descButtons.forEach(btn =>
                btn.classList.toggle('active', btn.dataset.mode === mode)
            );
        }

        descButtons.forEach(btn => {
            btn.addEventListener('click', () => applyDescMode(btn.dataset.mode));
        });

        applyDescMode('short');

        /* ================= CHARTS ================= */

        // Inventory Trend
    let trendChart = null;

    const trendCtx = document.getElementById('inventoryTrend');
    if (trendCtx) {
        trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: @json($trendLabels),
                datasets: [{
                    label: 'Inventory Value (₱)',
                    data: @json($trendValues),
                    borderColor: '#fd7e14',
                    backgroundColor: 'rgba(253,126,20,.2)',
                    fill: true,
                    tension: .4
                }]
            },
           options: {
    maintainAspectRatio: false,
    plugins: {
        datalabels: {
            display: false   // ✅ REMOVE NUMBERS FROM LINE CHART
        }
    }
}

        });
    }
    const yearSelect = document.getElementById('yearSelect');

    if (yearSelect && trendChart) {
    yearSelect.addEventListener('change', () => {
        const division = '{{ request('division') }}';

        let url = `{{ url('/inventory') }}?year=${yearSelect.value}`;
        if (division) {
            url += `&division=${encodeURIComponent(division)}`;
        }

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })

            .then(r => r.json())
            .then(d => {
                trendChart.data.labels = d.labels;
                trendChart.data.datasets[0].data = d.values;
                trendChart.update();
            });
        });
    }

      // ================= PIE CHARTS =================

// 🎯 Executive color logic: Top 3 highlighted, rest muted
function generateColorsByRank(values) {
    const highlightColors = [
        '#dc3545', // red
        '#ffc107', // yellow
        '#28a745', // green
        '#6f42c1', // purple
        '#0d6efd', // blue
        '#fd7e14', // orange
        '#20c997', // teal
        '#e83e8c'  // pink
    ];

    const muted = '#dee2e6'; // lighter gray

    return values.map((_, index) =>
        highlightColors[index % highlightColors.length]
    );
}



function pieOptions() {
    return {
        maintainAspectRatio: false,
        elements: {
            arc: {
                borderColor: '#ffffff',
                borderWidth: 2
            }
        },
        plugins: {
            legend: {
                display: false
            },


            // 🔥 THIS IS THE KEY FIX
            datalabels: {
                display: false
            },

            tooltip: {
                callbacks: {
                    label(ctx) {
                        const value = ctx.raw;
                        const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                        const pct = ((value / total) * 100).toFixed(1);
                        return `₱${value.toLocaleString()} (${pct}%)`;
                    }
                }
            }
        }
    };
}


// Warehouse Pie
const warehousePie = document.getElementById('warehousePie');
if (warehousePie) {
    new Chart(warehousePie, {
        type: 'pie',
        data: {
            labels: @json($warehouseLabels),
            datasets: [{
                data: @json($warehouseValues),
                backgroundColor: generateColorsByRank(@json($warehouseValues)),
                hoverOffset: 6
            }]
        },
        options: pieOptions()
    });
}

// Division Pie
const divisionPie = document.getElementById('divisionPie');
if (divisionPie) {
    new Chart(divisionPie, {
        type: 'pie',
        data: {
            labels: @json($divisionLabels),
            datasets: [{
                data: @json($divisionValues),
                backgroundColor: generateColorsByRank(@json($divisionValues)),
                hoverOffset: 6
            }]
        },
        options: pieOptions()
    });
}

   const healthCtx = document.getElementById('stockHealth');
if (healthCtx) {
    new Chart(healthCtx, {
        type: 'doughnut',
        data: {
            labels: ['Stock on Hand', 'Out of Stock'],
            datasets: [{
                data: [{{ $inStock }}, {{ $outOfStock }}],
                backgroundColor: ['#fd7e14', '#ffd8b0']
            }]
        },
        options: {
            cutout: '70%',
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                datalabels: {
                    color: '#495057',
                    font: {
                        weight: '600',
                        size: 12
                    },
                    formatter(value, ctx) {
                        const total = ctx.chart.data.datasets[0].data
                            .reduce((a, b) => a + b, 0);

                        const pct = (value / total) * 100;
                        if (pct < 5) return null;
                        return pct.toFixed(1) + '%';
                    }
                },
                tooltip: {
                    callbacks: {
                        label(ctx) {
                            const value = ctx.raw;
                            const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                            const pct = ((value / total) * 100).toFixed(1);
                            return `${ctx.label}: ${value.toLocaleString()} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
}



    });
    </script>
    @endpush


        @endsection
