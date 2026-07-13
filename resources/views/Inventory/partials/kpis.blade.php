<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="inv-kpi">
            <div class="inv-kpi-label">Inventory Value</div>
            <div class="inv-kpi-value">₱{{ number_format($inventoryTotal,2) }}</div>
            <small>Total stock valuation</small>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="inv-kpi">
            <div class="inv-kpi-label">Aging Stock (90+ Days)</div>
            <div class="inv-kpi-value">₱{{ number_format($agingValue,2) }}</div>
            <small>Slow / dead inventory</small>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="inv-kpi">
            <div class="inv-kpi-label">Reorder Alerts</div>
            <div class="inv-kpi-value">{{ number_format($reorderCount) }}</div>
            <small>Items below threshold</small>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="inv-kpi">
            <div class="inv-kpi-label">Total SKUs</div>
            <div class="inv-kpi-value">{{ number_format($totalItems) }}</div>
            <small>Active products</small>
        </div>
    </div>
</div>
