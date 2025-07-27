@extends('layouts/layoutMaster')
@php($customPageName = tr_helper('contents', 'LIST') . ' ' . tr_helper('contents', 'SERVER'))

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
													<i class="bx bx-edit-alt me-1"></i> Edit
												</a>
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
						<div class="mt-3">
							{{ $servers->links() }}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
