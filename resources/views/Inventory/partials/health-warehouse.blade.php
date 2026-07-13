<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="inv-card">
            <h6 class="mb-3">Stock Health</h6>
            <div class="row align-items-center">
                <div class="col-md-5 text-center">
                    <canvas id="stockHealth"></canvas>
                </div>
                <div class="col-md-7">
                    <div class="inv-kpi-label">Stock Availability</div>
                    <div class="inv-kpi-value">{{ number_format($availabilityRate,1) }}%</div>
                    <small>SKUs currently available</small>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="inv-kpi-label">In Stock</div>
                            <strong>{{ number_format($inStock) }}</strong>
                        </div>
                        <div class="text-end">
                            <div class="inv-kpi-label">Out of Stock</div>
                            <strong>{{ number_format($outOfStock) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="inv-card">
            <h6 class="mb-3">Warehouse Inventory Distribution</h6>
            <canvas id="warehouseChart"></canvas>
        </div>
    </div>
</div>
