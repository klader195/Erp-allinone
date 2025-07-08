@extends('layouts.app')
@section('title', __('Dispatch Edit'))

		@section('content')
		<!--start page wrapper -->
		<div class="page-wrapper">
			<div class="page-content">
				<x-breadcrumb :langArray="[
											'Dispatch',
											'Edit',
										]"/>
				<div class="row">
					<div class="col-12 col-lg-12">
                        <div class="card">
                            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                              <h5 class="mb-0">{{ __('Dispatch Edit') }}</h5>
                            </div>
                            <div class="card-body p-4">
                                    <form method="POST" class="row g-3 needs-validation" id="dispatcheditForm" action="{{ route('dispatch.store') }}" enctype="multipart/form-data">
    
                                    @csrf
                                    @method('POST')
                                    <input type="hidden" id="base_url" value="{{ url('/') }}">
                                    <input type="hidden" name="approved_by" value='{{ $dispatch->id }}'>
                                    <input type="hidden" name="operation" id="operation" value='update'>
                                    <input type="hidden" name="dispatch_id" value="{{ $dispatch->id }}">
                                    <input type="hidden" name="purchase_order_id" value="{{ $dispatch->purchase_order_id }}">
                                    <div class="col-md-4">
                                        <x-label for="item_id" name="{{ __('Work Order') }}"/>
                                        <div class="input-group">
                                            <br><b>{{ $dispatch->purchase_order_identifier }}</b>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <x-label for="customer_id" name="{{ __('Customer') }}"/>
                                        <div class="input-group">
                                            <br><b>{{ $dispatch->customer->first_name . ' ' . $dispatch->customer->last_name }}</b>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-none">
                                        <x-label for="remarks" name="{{ __('Mode Of Delivery') }}"/>
                                        <div class="input-group">
                                            <br><b>{{ $dispatch->mode_of_delivery }}</b>
                                        </div>
                                    </div>
                                     <div class="col-md-4 dispatch-class">
                                        <x-label for="mode_of_delivery" name="Mode OF Delivery" />
                                        <!--<x-dropdown-dispatch-type dropdownName="dispatch_status" />-->
                                        <x-dropdown-dispatch-status selected="{{ $dispatch->mode_of_delivery }}" dropdownName='mode_of_delivery'/>
                                    </div>
                                    <div class="col-md-4">
                                        <x-label for="remarks" name="{{ __('Remarks') }}"/>
                                        <div class="input-group">
                                            <!--<br><b>{{ $dispatch->remarks }}</b>-->
                                            <x-textarea name="remarks" value="{{ $dispatch->remarks }}"/>
                                        </div>
                                    </div>
                                  
                                    <div class="col-md-3 dispatch-class">
                                        <x-label for="dispatch-type" name="Dispatch Status" />
                                        <!--<x-dropdown-dispatch-type dropdownName="dispatch_status" />-->
                                        <x-dropdown-dispatch-type selected="{{ $dispatch->status }}" dropdownName='dispatch_status'/>
                                    </div>
                                   
                                    <div class="col-12">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Sl</th>
                                                    <th>Products</th>
                                                    <th>Quantity</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($dispatch->purchaseOrder->items as $orderItem)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $orderItem->product->name ?? 'Product Not Found' }}</td>
                                                        <td>{{ $orderItem->quantity }}</td>
                                                        <td>{{ $orderItem->product_remarks ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-12 mb-3 px-4 text-end">
                                        <div class="gap-3">
                                            <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
                                            <x-button type="submit" class="primary px-4" text="{{ __('app.submit') }}" />
                                        </div>
                                    </div>
                                </form>
                                
                                
                                


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
<script src="{{ versionedAsset('custom/js/sale/dispatch-edit.js') }}"></script>
@endsection
