@php
	// Get the current URL path and split into segments
	$segments = collect(request()->segments())->filter(function ($segment) {
		return $segment !== 'panel'; // Remove 'panel' if it exists
	})->values()->toArray();

	$length = count($segments);
	$last = last($segments);
	unset($segments[$length-1]);

	$breadcrumbParts = array_map(function ($segment) {
		return tr_helper('contents', ucfirst($segment));
	}, $segments);

	// Handle the breadcrumb display
	$breadcrumbText = implode(' / ', $breadcrumbParts);
@endphp

<h4 class="py-3 mb-4">
	<span class="text-muted fw-light">{!! $breadcrumbText !!} /</span> {{ tr_helper('contents', ucfirst($last)) }}
</h4>
