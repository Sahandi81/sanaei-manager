@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'List') . ' ' . tr_helper('contents', 'Cards'))

@section('title', $customPageName)

@section('content')
	<div class="container-xxl flex-grow-1 container-p-y">
		@include('components.pagePath')
		@include('components.errors')

		<div class="row">
			<div class="col-12">
				<div class="card">
					<h5 class="card-header d-flex justify-content-between align-items-center">
						{{ $customPageName }}
						<a href="{{ route('finance.cards.create') }}" class="btn btn-sm btn-primary">
							<i class="bx bx-plus"></i> {{ tr_helper('contents', 'Create') }}
						</a>
					</h5>
					<div class="table-responsive text-nowrap">
						<table class="table">
							<thead>
							<tr>
								<th>#</th>
								<th>{{ tr_helper('validation', 'attributes.user_id') }}</th>
								<th>{{ tr_helper('validation', 'attributes.owner_name') }}</th>
								<th>{{ tr_helper('validation', 'attributes.bank_name') }}</th>
								<th>{{ tr_helper('validation', 'attributes.card_number') }}</th>
								<th>{{ tr_helper('validation', 'attributes.is_default') }}</th>
								<th>{{ tr_helper('contents', 'Actions') }}</th>
							</tr>
							</thead>
							<tbody>
							@forelse($cards as $card)
								<tr>
									<td>{{ $loop->iteration }}</td>
									<td>{{ $card->user?->name }}</td>
									<td>{{ $card->owner_name }}</td>
									<td>{{ $card->bank_name }}</td>
									<td class="ltr">{{ \Illuminate\Support\Str::mask($card->card_number, '*', 0, strlen($card->card_number)-4) }}</td>
									<td>
                                    <span class="badge bg-label-{{ $card->is_default ? 'success':'secondary' }}">
                                        {{ $card->is_default ? tr_helper('contents','Yes') : tr_helper('contents','No') }}
                                    </span>
									</td>
									<td>
										<div class="dropdown">
											<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
												<i class="bx bx-dots-vertical-rounded"></i>
											</button>
											<div class="dropdown-menu">
												<a class="dropdown-item" href="{{ route('finance.cards.edit', $card->id) }}">
													<i class="bx bx-edit-alt me-1"></i> {{ tr_helper('contents', 'Edit') }}
												</a>

												@if(!$card->is_default)
													<form action="{{ route('finance.cards.setDefault', $card->id) }}" method="POST">
														@csrf
														<button class="dropdown-item" type="submit">
															<i class="bx bx-check-circle me-1"></i> {{ tr_helper('contents','SetDefault') }}
														</button>
													</form>
												@endif

{{--												<button type="button"--}}
{{--														class="dropdown-item text-danger open-delete-modal"--}}
{{--														data-bs-toggle="modal"--}}
{{--														data-bs-target="#deleteModal"--}}
{{--														data-item-name="{{ $card->owner_name }}"--}}
{{--														data-delete-route="{{ route('finance.cards.destroy', $card->id) }}">--}}
{{--													<i class="bx bx-trash me-1"></i> {{ tr_helper('contents', 'Delete') }}--}}
{{--												</button>--}}
											</div>
										</div>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="7" class="text-center text-muted">
										{{ tr_helper('contents', 'NoRecordsFound') }}
									</td>
								</tr>
							@endforelse
							</tbody>
						</table>
					</div>
				</div>

				<div class="mt-3">
					{{ $cards->links('vendor.pagination.bootstrap-5') }}
				</div>
			</div>
		</div>
	</div>

{{--	@include('components.delete-modal', [--}}
{{--		'modalId' => 'deleteModal',--}}
{{--		'title' => tr_helper('contents', 'DeleteCard'),--}}
{{--		'message' => tr_helper('contents', 'AreYouSureToDeleteThisItem'),--}}
{{--	])--}}
@endsection
