@extends('layouts/layoutMaster')
@php($isEdit = isset($card))
@php($customPageName = tr_helper('contents', $isEdit ? 'Edit' : 'Create') . ' ' . tr_helper('contents', 'Card'))

@section('title', $customPageName)

@section('content')
	<div class="container-xxl flex-grow-1 container-p-y">
		@include('components.pagePath')
		@include('components.errors')

		<div class="row">
			<div class="col-12">
				<div class="card">
					<h5 class="card-header">{{ $customPageName }}</h5>

					<div class="card-body">
						<form method="POST"
							  action="{{ $isEdit ? route('finance.cards.update', $card->id) : route('finance.cards.store') }}"
							  class="row g-3" data-dynamic-validation>
							@csrf
							<input type="hidden" name="form" value="{{ $isEdit ? 'UPDATE' : 'CREATE' }}">

							@if(auth()->user()->role->is_admin)
								<x-form.form-input
									name="user_id"
									type="select"
									:options="$users->pluck('name','id')"
									:value="$isEdit ? $card->user_id : null"
									required
								/>
							@endif

							<x-form.form-input
								name="owner_name"
								:value="$isEdit ? $card->owner_name : null"
								required
								:validation="['maxLength' => 255]"
							/>

							<x-form.form-input
								name="bank_name"
								:value="$isEdit ? $card->bank_name : null"
								required
								:validation="['maxLength' => 255]"
							/>

							<x-form.form-input
								name="card_number"
								type="text"
								:value="$isEdit ? $card->card_number : null"
								required
								:validation="['maxLength' => 32]"
							/>

							<x-form.form-input
								name="is_default"
								type="select"
								:options="[1 => tr_helper('contents','Yes'), 0 => tr_helper('contents','No')]"
								:value="$isEdit ? (int) $card->is_default : 0"
							/>

							<div class="col-12">
								<button type="submit" class="btn btn-primary btn-9rem" data-submit-button>
									<span class="btn-text">{{ $isEdit ? tr_helper('contents', 'Update') : tr_helper('contents','Create') }}</span>
									<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
