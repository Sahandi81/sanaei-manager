@php
	use Illuminate\Support\Facades\Auth;

	$configData = App\Helpers\Helpers::appClasses();
	$userRole = strtolower(Auth::user()->role_key ?? '');
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
	@if(!isset($navbarFull))
		<div class="app-brand demo">
			<a href="{{url('/')}}" class="app-brand-link mx-auto">
      <span class="app-brand-logo demo m-auto">
        @include('_partials.macros',["width"=>80,"withbg"=>'var(--bs-primary)'])
      </span>
				<span class="app-brand-text demo menu-text fw-bold ms-2">{{tr_helper('contents', config('variables.templateName'))}}</span>
			</a>

			<a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
				<i class="bx bx-chevron-left bx-sm align-middle"></i>
			</a>
		</div>
	@endif

	<div class="menu-inner-shadow"></div>
	<ul class="menu-inner py-1">
		@php
			$menuItems = $menuData[0]->menu;
		@endphp

		@foreach ($menuItems as $menu)

			{{-- هدرهای منو --}}
			@if (isset($menu->menuHeader))
				<li class="menu-header small text-uppercase">
					<span class="menu-header-text">{{ tr_helper('contents', tr_helper('contents',$menu->menuHeader)) }}</span>
				</li>
				@continue
			@endif

			{{-- کنترل دسترسی بر اساس roles --}}
			@php
				// اجازه آیتم والد
				$itemRoles = isset($menu->roles) ? array_map('strtolower', (array) $menu->roles) : null;
				$isAllowed = is_null($itemRoles) || in_array($userRole, $itemRoles);

				// فیلتر ساب‌منو‌ها بر اساس roles (اگر داشتند)
				$filteredSubmenu = [];
				if (isset($menu->submenu)) {
				  $filteredSubmenu = array_values(array_filter($menu->submenu, function ($sub) use ($userRole) {
					if (!isset($sub->roles)) return true;
					$subRoles = array_map('strtolower', (array) $sub->roles);
					return in_array($userRole, $subRoles);
				  }));
				}

				// اگر آیتم والد ساب‌منو دارد ولی هیچکدام مجاز نبودند، والد را هم پنهان کن مگر اینکه خودش url مستقیم داشته باشد
				$hasVisibleChildren = isset($menu->submenu) ? count($filteredSubmenu) > 0 : true;
				$shouldRender = $isAllowed && $hasVisibleChildren;

				// اکتیو/اوپن
				$activeClass = null;
				$currentRouteName = Route::currentRouteName();
				if ($currentRouteName === $menu->slug) {
				  $activeClass = 'active';
				} elseif (isset($menu->submenu)) {
				  if (gettype($menu->slug) === 'array') {
					foreach($menu->slug as $slug){
					  if (str_contains($currentRouteName,$slug) && strpos($currentRouteName,$slug) === 0) {
						$activeClass = 'active open';
					  }
					}
				  } else {
					if (str_contains($currentRouteName,$menu->slug) && strpos($currentRouteName,$menu->slug) === 0) {
					  $activeClass = 'active open';
					}
				  }
				}
			@endphp

			@if($shouldRender)
				<li class="menu-item {{$activeClass}}">
					<a href="{{ isset($menu->url) ? route($menu->url) : 'javascript:void(0);' }}"
					   class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
					   @if (isset($menu->target) && !empty($menu->target)) target="_blank" @endif>
						@isset($menu->icon)
							<i class="{{ $menu->icon }}"></i>
						@endisset
						<div class="text-truncate">{{ isset($menu->name) ? __(tr_helper('contents',$menu->name)) : '' }}</div>
						@isset($menu->badge)
							<div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
						@endisset
					</a>

					{{-- submenu --}}
					@isset($menu->submenu)
						@if(count($filteredSubmenu) > 0)
							@include('layouts.sections.menu.submenu',['menu' => $filteredSubmenu])
						@endif
					@endisset
				</li>
			@endif

		@endforeach
	</ul>
</aside>

