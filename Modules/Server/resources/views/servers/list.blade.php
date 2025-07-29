@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'List') . ' ' . tr_helper('contents', 'Server'))

@section('title', $customPageName)

@section('content')
	<div class="container-xxl flex-grow-1 container-p-y">
		@include('components.pagePath')
		@include('components.errors')

		<div class="row">
			<div class="col-12">
				<div class="card">
					<h5 class="card-header">{{ $customPageName }}</h5>
					<div class="table-responsive text-nowrap">
						<table class="table">
							<thead>
							<tr>
								<th>#</th>
								<th> {{tr_helper('validation', 'attributes.name')}} </th>
								<th> {{tr_helper('validation', 'attributes.ip')}} </th>
								<th> {{tr_helper('validation', 'attributes.location')}} </th>
								<th> {{tr_helper('validation', 'attributes.panel_type')}} </th>
								<th> {{tr_helper('validation', 'attributes.api_url')}} </th>
								<th> {{tr_helper('validation', 'attributes.status')}} </th>
								<th> {{tr_helper('validation', 'attributes.created_at')}} </th>
								<th> {{tr_helper('contents', 'Actions')}} </th>
							</tr>
							</thead>
							<tbody class="table-border-bottom-0">
							@forelse($servers as $server)
								<tr>
									<td>{{ $loop->iteration }}</td>
									<td>{{ $server->name }}</td>
									<td>{{ $server->ip }}</td>
									<td>{{ $server->location ?? '-' }}</td>
									<td>{{ ucfirst($server->panel_type) }}</td>
									<td><a href="{{$server->api_url}}"><small class="text-muted">{{ $server->api_url }}</small></a></td>
									<td>
										@php($status = (Modules\Server\Models\Server::getStatues()[$server->status] ?? ['text' => 'Unknown', 'status' => 'secondary']))

										<span class="badge bg-label-{{ $status['status'] }}">
											{{ $status['text'] }}
										</span>
									</td>
									<td>{{ $server->created_at->format('Y-m-d H:i') }}</td>
									<td>
										<div class="dropdown">
											<button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
												<i class="bx bx-dots-vertical-rounded"></i>
											</button>
											<div class="dropdown-menu">
												<a class="dropdown-item" href="{{ route('servers.edit', $server->id) }}">
													<i class="bx bx-edit-alt me-1"></i> {{ tr_helper('contents', 'Edit') }}
												</a>
												<form action="" method="POST">
													@csrf
													@method('DELETE')
													<button type="button"
															data-bs-toggle="modal"
															data-bs-route="{{ route('servers.destroy', $server->id) }}"
															data-bs-target="#deleteServerModal"
															data-server-id="{{ $server->id }}"
															data-server-name="{{ $server->name }}"
															class="dropdown-item text-danger open-delete-modal">
														<i class="bx bx-trash me-1"></i> {{ tr_helper('contents', 'Delete') }}
													</button>
												</form>
											</div>

										</div>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="9" class="text-center text-muted">No servers found.</td>
								</tr>
							@endforelse
							</tbody>
						</table>

					</div>

				</div>
				<div class="mt-3">
					{{ $servers->links('vendor.pagination.bootstrap-5') }}
				</div>
			</div>
		</div>





		<!-- Delete Confirmation Modal -->
		<div class="modal fade" id="deleteServerModal" tabindex="-1" aria-labelledby="deleteServerModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<form method="POST" id="deleteServerForm" class="w-100">
					@csrf
					@method('DELETE')

					<div class="modal-content border-0 shadow-lg rounded-4">
						<div class="modal-body text-center p-5">

							<div class="mb-4">
								<div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle" style="width: 64px; height: 64px;">
									<i class="bx bx-trash fs-1"></i>
								</div>
							</div>

							<h4 class="fw-bold mb-3">{{ tr_helper('contents', 'AreYouSure') }}</h4>

							<p class="text-muted mb-4">
								{{ tr_helper('contents', 'ThisActionCannotBeUndone') }}
							</p>

							<p class="fw-semibold fs-6 text-dark mb-4 server-name-placeholder">
								<!-- Filled by JS -->
							</p>

							<div class="d-flex justify-content-center gap-2">
								<button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
									<i class="bx bx-x me-1"></i> {{ tr_helper('contents', 'Cancel') }}
								</button>

								<button type="submit" class="btn btn-danger px-4">
									<i class="bx bx-trash me-1"></i> {{ tr_helper('contents', 'DeleteNow') }}
								</button>
							</div>

						</div>
					</div>
				</form>
			</div>
		</div>


		<script>
			document.addEventListener('DOMContentLoaded', function () {
				const modal = document.getElementById('deleteServerModal');
				const form = document.getElementById('deleteServerForm');
				const namePlaceholder = modal.querySelector('.server-name-placeholder');

				document.querySelectorAll('.open-delete-modal').forEach(button => {
					button.addEventListener('click', () => {
						const serverName = button.getAttribute('data-server-name');
						const deleteRoute = button.getAttribute('data-bs-route');

						form.setAttribute('action', deleteRoute);
						namePlaceholder.textContent = `« ${serverName} »`;
					});
				});
			});

		</script>
	</div>
@endsection
