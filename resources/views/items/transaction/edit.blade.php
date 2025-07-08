@extends('layouts.app')
@section('title', __('item.edit'))

		@section('content')
		<!--start page wrapper -->
		<div class="page-wrapper">
			<div class="page-content">
				<x-breadcrumb :langArray="[
											'Stock',
											'item.edit',
										]"/>
				<div class="row">
					<div class="col-12 col-lg-12">
                        <div class="card">
                            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                              <h5 class="mb-0">{{ __('item.edit') }}</h5>
                            </div>
                            <div class="card-body p-4">
                                    <form  class="row g-3 needs-validation" id="itemForm1" method="POST" action="{{ route('item.transaction.update', $item->item_id) }}" enctype="multipart/form-data">
    
                                    @csrf
                                    @method('POST')
                                    <input type="hidden" id="base_url" value="{{ url('/') }}">
                                    <input type="hidden" name="operation" id="operation" value='update'>
                                    <div class="col-lg-4 col-md-4">
                                        <x-label for="item_id" name="{{ __('item.item_name') }}"/>
                                        <div class="input-group">
                                            <br><b>{{ $item->item->name }}</b>
                                            <input type="hidden" name="item_id" value="{{ $item->item_id }}">
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-2 col-md-4">
                                        <x-label for="quantity" name="{{ __('Current Stock') }}" />
                                        <x-input type="number" name="quantity" id="quantity" :required="true"  value="{{ $item->quantity }}"/>
                                    </div>

                                   

                                    </div>

                                    <div class="col-md-12 mb-3 px-4 text-end">
                                        <div class="gap-3">
                                            <x-anchor-tag href="{{ route('item.transaction.stocklist') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
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
<!--<script src="{{ versionedAsset('custom/js/items/stock.js') }}"></script>-->
@endsection
