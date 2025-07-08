@extends('layouts.app')
@section('title', __('item.edit'))

@section('content')
    <!--start page wrapper -->
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumb :langArray="['item.production', 'Production And Packing Tracking']" />
            <div class="row">
                <div class="col-12 col-lg-12">
                    <div class="card">
                        <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ __('Production And Packing Tracking') }}</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <x-label for="item_id" name="{{ __('Purchase Order') }}" />
                                    @if ($productionItemMaster->purchaseOrder)
                                        <div class="input-group">
                                            <br><b>{{ $productionItemMaster->purchaseOrder->purchase_order_id }}</b>
                                        </div>
                                    @else
                                        <div class="input-group">
                                            <br><b>Not Available</b>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <x-label for="item_id" name="{{ __('item.item_name') }}" />
                                    <div class="input-group">
                                        <br><b>{{ $productionItemMaster->item->name }}</b>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <x-label for="item_id" name="{{ __('Product Brand') }}" />
                                    <div class="input-group">
                                        <br><b>{{ $productionItemMaster->item->brand->name ?? 'Not Available' }}</b>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <x-label for="item_id" name="{{ __('Product Category') }}" />
                                    <div class="input-group">
                                        <br><b>{{ $productionItemMaster->item->category->name ?? 'Not Available' }}</b>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-4">
                                    <x-label for="item_id" name="{{ __('Production Type') }}" />
                                    <div class="input-group">
                                        <br><b>{{ $productionItemMaster->production_type ?? 'Not Available' }}</b>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-4">
                                    <x-label for="item_id" name="{{ __('Requested Quantity') }}" />
                                    <div class="input-group">
                                        <br><b>{{ $productionItemMaster->requested_qty ?? 'Not Available' }}</b>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-4">
                                    <x-label for="item_id" name="{{ __('Ordered Date') }}" />
                                    <div class="input-group">
                                        <br><b>
                                            {{ $productionItemMaster->purchaseOrder->po_date
                                                ? \Carbon\Carbon::parse($productionItemMaster->purchaseOrder->po_date)->format('d M Y')
                                                : 'Not Available' }}</b>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-4">
                                    <x-label for="item_id" name="{{ __('Due Date') }}" />
                                    <div class="input-group">
                                        <br><b>
                                            {{ $productionItemMaster->purchaseOrder->due_date
                                                ? \Carbon\Carbon::parse($productionItemMaster->purchaseOrder->due_date)->format('d M Y')
                                                : 'Not Available' }}</b>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-4">
                                    <x-label for="item_id" name="{{ __('Production Remaining Qty') }}" />
                                    <div class="input-group">
                                        <br><b>
                                            {{ $productionItemMaster->requested_qty - $productionItemMaster->productionLists()->sum('quantity') }}</b>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-4">
                                    <x-label for="item_id" name="{{ __('Packing Remaining Qty') }}" />
                                    <div class="input-group">
                                        <br><b>
                                            {{ $productionItemMaster->requested_qty - $productionItemMaster->packingLists()->sum('quantity') }}</b>
                                    </div>
                                </div>
                                @php
                                    $status = $productionItemMaster->status ?? null;
                                    $badgeClasses = [
                                        'Pending' => 'badge bg-warning text-dark',
                                        'Packing Pending' => 'badge bg-warning text-dark',
                                        'Completed' => 'badge bg-success',
                                        'Partial' => 'badge bg-info text-dark',
                                        'Progress' => 'badge bg-primary',
                                        'Cancelled' => 'badge bg-danger',
                                    ];
                                @endphp
                                <div class="col-md-3 mt-4">
                                    <x-label for="item_id" name="{{ __('Status') }}" />
                                    <div class="input-group">
                                        <br>
                                        @if ($status)
                                            <span
                                                class="badge rounded-pill px-3 py-2 fw-semibold {{ $badgeClasses[$status] ?? 'badge bg-secondary' }}">
                                                {{ $status }}
                                            </span>
                                        @else
                                            <span class="text-muted">Not Available</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 mt-2">
                                <div class="row">
                                    <div class="col-md-12 d-flex justify-content-center">
                                        <h5>Production Progress</h5>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="row mt-4">
                                                    <div class="col-md-12">
                                                        <x-label for="item_id"
                                                            name="{{ __('Production Completed Quantity') }}" />
                                                        <div class="input-group">
                                                            <br><b>{{ $productionItemMaster->productionLists->sum('quantity') }}</b>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12 mt-2">
                                                        @php
                                                            $status = $productionItemMaster->production_status ?? null;
                                                            $badgeClasses = [
                                                                'Pending' => 'badge bg-warning text-dark',
                                                                'Completed' => 'badge bg-success',
                                                                'Partial' => 'badge bg-primary',
                                                                'Progress' => 'badge bg-info',
                                                                'Cancelled' => 'badge bg-danger',
                                                            ];
                                                        @endphp
                                                        <x-label for="item_id" name="{{ __('Production Status') }}" />
                                                        <div class="input-group">
                                                            @if ($status)
                                                                <span
                                                                    class="badge rounded-pill px-3 py-2 fw-semibold {{ $badgeClasses[$status] ?? 'badge bg-secondary' }}">
                                                                    {{ $status }}
                                                                </span>
                                                            @else
                                                                <span class="text-muted">Not Available</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-9 mt-3">
                                                <form class="row g-3 needs-validation" id="productionForm"
                                                    action="{{ route('item.production.store-production') }}"
                                                    enctype="multipart/form-data">
                                                    @csrf
                                                    @method('POST')
                                                    <input type="hidden" name="production_id"
                                                        value="{{ $productionItemMaster->id }}">
                                                    <div class="col-md-3 mt-2">
                                                        <x-label for="production_qty" name="{{ __('Quantity') }}" />
                                                        <input type="number" name="production_qty" id="production_qty"
                                                            value="" class="form-control">
                                                    </div>
                                                    <div class="col-md-3 mt-2">
                                                        <x-label for="user_id" name="{{ __('Produced By') }}" />
                                                        <div class="input-group">
                                                            <x-dropdown-entered :showSelectOptionAll=true
                                                                :required="true" />
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3  mt-2">
                                                        <x-label for="machines" name="{{ __('Machine') }}" />
                                                        <div class="input-group">
                                                            <x-dropdown-machines dropdownName='machines'
                                                                :showSelectOptionAll=true :required="true" />
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 mt-2">
                                                        <x-label for="real_number" name="{{ __('Real Number') }}" />
                                                        <input type="number" name="real_number" id="real_number"
                                                            value="" class="form-control">
                                                    </div>
                                                    <div class="col-md-12 mb-3 px-4 text-end">
                                                        <div class="gap-3">
                                                            <x-button type="submit" class="primary px-4"
                                                                text="{{ __('Save') }}" />
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 mt-2">
                                <div class="row">
                                    <div class="col-md-12 d-flex justify-content-center">
                                        <h5>Packing Progress</h5>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="row mt-4">
                                                    <div class="col-md-12">
                                                        <x-label for="item_id"
                                                            name="{{ __('Packing Completed Quantity') }}" />
                                                        <div class="input-group">
                                                            <br><b>{{ $productionItemMaster->packingLists->sum('quantity') }}</b>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12 mt-2">
                                                        @php
                                                            $status = $productionItemMaster->packing_status ?? null;
                                                            $badgeClasses = [
                                                                'Pending' => 'badge bg-warning text-dark',
                                                                'Completed' => 'badge bg-success',
                                                                'Partial' => 'badge bg-primary',
                                                                'Progress' => 'badge bg-info',
                                                                'Cancelled' => 'badge bg-danger',
                                                            ];
                                                        @endphp
                                                        <x-label for="item_id" name="{{ __('Packing Status') }}" />
                                                        <div class="input-group">
                                                            @if ($status)
                                                                <span
                                                                    class="badge rounded-pill px-3 py-2 fw-semibold {{ $badgeClasses[$status] ?? 'badge bg-secondary' }}">
                                                                    {{ $status }}
                                                                </span>
                                                            @else
                                                                <span class="text-muted">Not Available</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-9 mt-3">
                                                <form class="row g-3 needs-validation" id="packingForm"
                                                    action="{{ route('item.production.store-packing') }}"
                                                    enctype="multipart/form-data">
                                                    @csrf
                                                    @method('POST')
                                                    <input type="hidden" name="production_id"
                                                        value="{{ $productionItemMaster->id }}">
                                                    <div class="col-md-6 mt-2">
                                                        <x-label for="packed_qty" name="{{ __('Quantity') }}" />
                                                        <input type="number" name="packed_qty" id="packed_qty"
                                                            value="" class="form-control">
                                                    </div>
                                                    <div class="col-md-6 mt-2">
                                                        <x-label for="packed_by" name="{{ __('Packed By') }}" />
                                                        <div class="input-group">
                                                            <x-dropdown-entered :showSelectOptionAll=true />
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12 mb-3 px-4 text-end">
                                                        <div class="gap-3">
                                                            <x-button type="submit" class="primary px-4"
                                                                text="{{ __('Save') }}" />
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 mt-2">
                                <div class="row">
                                    <div class="col-md-12 d-flex justify-content-center">
                                        <h5>Remarks</h5>
                                    </div>
                                    <div class="col-md-12">
                                        <h6>Production Remarks</h6>
                                        <div class="input-group mt-0">
                                            {{ $productionItemMaster->production_remarks }}
                                        </div>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <h6>Packing Remarks</h6>
                                        <div class="input-group mt-0">
                                            {{ $productionItemMaster->packing_remarks }}
                                        </div>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <h6>Dispatch Remarks</h6>
                                        <div class="input-group mt-0">
                                            {{ $productionItemMaster->dispatch_remarks }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end row-->
        </div>
    </div>
    <!-- Import Modals -->


@endsection

@section('js')
    <script src="{{ versionedAsset('custom/js/items/production.js') }}"></script>
@endsection
