<!doctype html>
<html lang="fa" dir="rtl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ $title ?? ('⚡️ ' . ($channelAt ?? '@Your_Channel') . ' | ' . ($clientName ?? 'کاربر')) }}</title>

	<!-- Tailwind (dev via CDN). برای پروداکشن با Vite/CLI بیلد کنید. -->
	<script src="https://cdn.tailwindcss.com"></script>
	<!-- Alpine.js (defer) -->
	<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

	<!-- Vazirmatn (فونت فارسی سبک‌تر) -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600&display=swap" rel="stylesheet">

	<style>
		:root{ --glass-bg: rgba(255,255,255,.75); --glass-border: rgba(255,255,255,.4) }
		html,body{ height:100%; }
		body{ font-family:'Vazirmatn', ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Noto Sans", "Helvetica Neue", Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
		body::before{ content:""; position:fixed; inset:0; z-index:-2; background:url('{{ asset('/subs-bg.jpg') }}') center/cover no-repeat; filter:saturate(1.05) contrast(1.05); }
		body::after{ content:""; position:fixed; inset:0; z-index:-1; background:radial-gradient(1200px 800px at 80% 20%, rgba(255,255,255,.6), rgba(255,255,255,.2) 40%, rgba(0,0,0,.35)); mix-blend-mode:screen; }
		.glass{ background:var(--glass-bg); backdrop-filter: blur(10px); border:1px solid var(--glass-border) }
		.btn{ display:inline-flex; align-items:center; gap:.5rem; border-radius:.75rem; padding:.5rem .8rem; transition:all .2s ease }
		.btn-dark{ background:#0f172a; color:#fff } .btn-dark:hover{ background:#0b1229; transform:translateY(-1px) }
		.btn-soft{ background:#e2e8f0 } .btn-soft:hover{ background:#cbd5e1; transform:translateY(-1px) }
		.icon{ width:18px; height:18px }
		[x-cloak]{ display:none !important }
		/***** Toast *****/
		.toast-enter{ opacity:0; transform:translateY(6px) scale(.98) }
		.toast-enter-active{ transition:all .25s ease }
		.toast-leave{ opacity:1 }
		.toast-leave-active{ opacity:0; transform:translateY(6px) scale(.98); transition:all .3s ease }
		.font-medium { font-size: 14px}
	</style>
</head>
<body class="min-h-full text-slate-800 selection:bg-amber-200/60"
	  x-data="subPage()" x-init="init()"
	  id="app"
	  data-channel-at="{{ $channelAt }}"
	  data-client-name="{{ $clientName }}"
	  data-title="{{ $title }}"
	  data-sub-url="{{ $subscriptionUrl }}"
	  data-configs='@json($configs ?? [])'
	  data-total-gb="{{ (float)($totalGB ?? 0) }}"
	  data-used-gb="{{ (float)($usedGB ?? 0) }}"
	  data-total-bytes="{{ (int)($totalBytes ?? 0) }}"
	  data-used-bytes="{{ (int)($usedBytes ?? 0) }}"
	  data-expires-at="{{ $expiresAt ?? '' }}"
	  data-reset-interval="{{ $resetInterval ?? 'no_reset' }}"
	  data-qr-url="{{ $qrUrl ?? '' }}">

<div class="max-w-5xl mx-auto px-4 py-6">
	<!-- ALERT: quota exceeded -->
	<div x-cloak x-show="overCap" x-transition.opacity.scale.origin.bottom class="glass border-red-300/70 bg-red-50/80 text-red-800 rounded-2xl p-4 mb-4">
		<div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
			<svg class="icon mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
			<div class="flex-1">
				<div class="font-medium">حجم شما تمام شده است</div>
				<div class="text-sm mt-0.5">برای تمدید اکانت، لطفاً به پشتیبانی تلگرام پیام بدهید.</div>
			</div>
			<a :href="`tg://resolve?domain=${channelAt.replace('@','')}`" class="sm:ml-auto btn btn-dark w-full sm:w-auto justify-center mt-2 sm:mt-0 shrink-0">ارتباط با پشتیبانی</a>
		</div>
	</div>

	<!-- HEADER -->
	<header class="glass rounded-2xl p-4">
		<div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
			<div class="flex items-center gap-3">
				<div class="w-12 h-12 rounded-2xl bg-amber-500/20 grid place-items-center"><span class="text-2xl">⚡️</span></div>
				<div>
					<h1 class="text-xl font-medium" x-text="channelAt"></h1>
					<p class="text-sm text-slate-600">پروفایل اشتراک برای <span class="font-medium" x-text="clientName"></span></p>
				</div>
			</div>
			<div class="flex gap-2 flex-wrap w-full sm:w-auto">
				<button @click="copyAll($event)" class="btn btn-dark w-full sm:w-auto justify-center">
					<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
					کپی همه
				</button>
				<a :href="`tg://resolve?domain=${channelAt.replace('@','')}`" class="btn btn-soft w-full sm:w-auto justify-center">تلگرام</a>
			</div>
		</div>
	</header>

	<!-- STATS -->
	<section class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
		<div class="glass p-4 rounded-2xl">
			<div class="text-sm text-slate-600">حجم باقی‌مانده</div>
			<div class="mt-1 text-2xl font-medium" x-text="formatGB(leftGB)"></div>
			<div class="mt-2 h-2 rounded bg-slate-200 overflow-hidden">
				<div class="h-full" :class="usageColor" :style="`width:${usagePercent}%`"></div>
			</div>
			<div class="mt-1 text-xs text-slate-600" x-text="`${formatGB(usedGB)} مصرف شده از ${formatGB(totalGB)}`"></div>
		</div>
		<div class="glass p-4 rounded-2xl">
			<div class="text-sm text-slate-600">تاریخ انقضا</div>
			<div class="mt-1 text-2xl font-medium" x-text="formatDate(expiresAt)"></div>
			<div class="mt-1 text-xs text-slate-600" x-text="resetLabel"></div>
		</div>
		<div class="glass p-4 rounded-2xl">
			<div class="text-sm text-slate-600">نام پروفایل</div>
			<div class="mt-1 text-2xl font-medium truncate" x-text="title"></div>
			<div class="mt-1 text-xs text-slate-600">نمایش در کلاینت‌هایی که پشتیبانی می‌کنند</div>
		</div>
	</section>

	<!-- QUICK INSTALL -->
	<section class="glass rounded-2xl p-4 mt-6">
		<h2 class="text-lg font-medium mb-3">نصب سریع</h2>
		<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 text-sm">
			<a id="ios-streisand" class="btn btn-dark justify-center">Streisand</a>
			<a id="ios-v2box" class="btn btn-dark justify-center">V2Box</a>
			<a id="ios-singbox" class="btn btn-dark justify-center">Sing-Box (iOS/Mac)</a>
			<a id="android-v2rayng" class="btn btn-soft justify-center">v2rayNG</a>
			<a id="android-singbox" class="btn btn-soft justify-center">Sing-Box (Android)</a>
			<a id="android-nekobox" class="btn btn-soft justify-center">NekoBox</a>
			<a id="android-hiddify" class="btn btn-soft justify-center">Hiddify</a>
			<a id="windows-clashverge" class="btn btn-soft justify-center">Clash/Clash Verge</a>
		</div>
		<p class="text-xs text-slate-600 mt-3">اگر باز نشد، در تنظیمات اجازهٔ بازشدن لینک‌های عمیق را بدهید.</p>
	</section>

	<!-- SUBSCRIPTION LINK + MAIN QR -->
	<section class="glass rounded-2xl p-4 mt-6">
		<h2 class="text-lg font-medium mb-3">لینک اشتراک</h2>
		<div class="flex flex-col sm:flex-row items-stretch gap-2">
			<input type="text" x-ref="subInput" class="flex-1 w-full rounded-xl border border-slate-300 bg-white/60 px-3 py-2 text-sm sm:text-base" :value="subUrl" readonly>
			<button @click="copyToClipboard($event, subUrl)" class="btn btn-dark w-full sm:w-auto justify-center" title="کپی لینک">
				<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
				کپی
			</button>
			<template x-if="qrMain">
				<button @click="openQrImage()" class="btn btn-soft w-full sm:w-auto justify-center" title="نمایش QR اشتراک">نمایش QR</button>
			</template>
		</div>
	</section>

	<!-- CONFIGS LIST (copy only) -->
	<section class="glass rounded-2xl p-4 mt-6">
		<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-3">
			<h2 class="text-lg font-medium">دسترسی تکی به کانفیگ‌ها</h2>
			<button @click="copyAll($event)" class="btn btn-dark w-full sm:w-auto justify-center">
				<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
				کپی همه
			</button>
		</div>
		<div id="cfgList" class="space-y-2"></div>
	</section>
</div>

<!-- QR MODAL (image) -->
<div x-cloak x-show="qr.open" @click.self="qr.open=false"
	 x-transition.opacity x-transition.scale.origin.center
	 class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4">
	<div class="bg-white rounded-2xl max-w-xl w-full p-4 shadow-xl">
		<div class="flex items-center justify-between mb-3">
			<h3 class="font-medium">QR اشتراک</h3>
			<button class="text-slate-500 hover:text-slate-700 transition" @click="qr.open=false">✕</button>
		</div>
		<div class="w-full grid place-items-center">
			<img :src="qr.image" alt="Subscription QR" class="rounded-xl shadow max-h-[70vh] w-auto">
		</div>
		<div class="mt-3 text-xs text-slate-600 break-all" x-text="subUrl"></div>
	</div>
</div>

<!-- TOASTS -->
<div aria-live="polite" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[60] space-y-2">
	<template x-for="t in toasts" :key="t.id">
		<div class="glass px-4 py-3 rounded-xl shadow border flex items-start gap-2 min-w-[260px]"
			 :class="t.type==='danger' ? 'border-red-300/70 bg-red-50/80 text-red-800' : (t.type==='success' ? 'border-emerald-300/70 bg-emerald-50/80 text-emerald-800' : 'border-blue-300/70 bg-blue-50/90 text-blue-800')"
			 x-transition:enter="transform duration-300 ease-out"
			 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
			 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
			 x-transition:leave="transform duration-200 ease-in"
			 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
			 x-transition:leave-end="opacity-0 translate-y-3 scale-95">
			<svg class="icon mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<template x-if="t.type==='success'"><path d="M20 6L9 17l-5-5"></path></template>
				<template x-if="t.type!=='success'"><circle cx="12" cy="12" r="10"></circle></template>
			</svg>
			<div class="text-sm" x-text="t.msg"></div>
			<button class="mr-auto text-slate-500 hover:text-slate-700" @click="dismissToast(t.id)">✕</button>
		</div>
	</template>
</div>

<!-- App script -->
<script>
	window.subPage = function(){
		return {
			// state
			channelAt: '@Satify_vpn', clientName: 'کاربر', title: '',
			subUrl: '', configs: [], totalGB: 0, usedGB: 0, totalBytes: 0, usedBytes: 0,
			expiresAt: '', resetInterval: 'no_reset', qrMain: '',
			usagePercent: 0, usageColor: 'bg-green-500', leftGB: 0, overCap: false,
			qr: { open:false, image:null },
			toasts: [],

			init(){
				try{
					const el = document.getElementById('app');
					const ds = el.dataset;
					this.channelAt     = ds.channelAt || this.channelAt;
					this.clientName    = ds.clientName || this.clientName;
					this.title         = ds.title || (`⚡️ ${this.channelAt} | ${this.clientName}`);
					this.subUrl        = ds.subUrl || '';
					this.configs       = JSON.parse(ds.configs || '[]');
					this.totalGB       = Number(ds.totalGb || 0);
					this.usedGB        = Number(ds.usedGb || 0);
					this.totalBytes    = Number(ds.totalBytes || 0);
					this.usedBytes     = Number(ds.usedBytes || 0);
					this.expiresAt     = ds.expiresAt || '';
					this.resetInterval = ds.resetInterval || 'no_reset';
					this.qrMain        = ds.qrUrl || '';

					// محاسبات ایمن
					if(!(this.totalGB > 0) && this.totalBytes > 0){ this.totalGB = this.safeGB(this.totalBytes); }
					if(!(this.usedGB >= 0) && this.usedBytes >= 0){ this.usedGB = this.safeGB(this.usedBytes); }
					this.totalGB = Math.max(0, Number(this.totalGB||0));
					this.usedGB  = Math.max(0, Number(this.usedGB||0));
					this.overCap = this.usedGB > this.totalGB && this.totalGB > 0;
					this.usedGB  = this.overCap ? this.totalGB : Math.min(this.usedGB, this.totalGB);
					this.leftGB  = Math.max(0, (this.totalGB||0) - (this.usedGB||0));

					this.usagePercent = this.calcPercent(this.usedGB, this.totalGB);
					this.usageColor   = this.getBarColor(this.usagePercent);

					this.setDeepLinks();
					this.renderConfigs();

					if(this.overCap){
						this.toast('حجم شما تمام شده است. برای تمدید اقدام کنید.', 'danger');
					}
				}catch(e){ console.error('Init error:', e); }
			},

			// helpers
			safeGB(bytes){ const v = Math.max(0, Number(bytes||0)); return v ? v/(1024**3) : 0; },
			formatGB(g){ const n = Number(g||0); if(n >= 1024) return (n/1024).toFixed(2).replace(/\.00$/,'') + ' TB'; return n.toFixed(n<10 ? 2 : (n<100?1:0)).replace(/\.0+$/,'') + ' GB'; },
			formatDate(iso){ if(!iso) return '∞'; try{ return new Date(iso).toLocaleString('fa-IR-u-nu-latn'); }catch(e){ return iso; } },
			get resetLabel(){ return (this.resetInterval === 'no_reset') ? 'بدون ریست ترافیک' : `ریست هر ${this.resetInterval}`; },
			calcPercent(used,total){ if(!total) return 0; const p=(Number(used)/Number(total))*100; return Math.min(100, Math.max(0, Number(p.toFixed(1)))); },
			getBarColor(p){ return p < 40 ? 'bg-green-500' : (p < 80 ? 'bg-yellow-600':'bg-red-500'); },

			setDeepLinks(){
				const u = encodeURIComponent(this.subUrl);
				const set = (id, href)=>{ const a=document.getElementById(id); if(a) a.href = href; };
				set('ios-streisand',      `streisand://import/${this.subUrl}`);
				set('ios-v2box',          `v2box://install-sub?url=${u}&name=${encodeURIComponent(this.clientName)}`);
				set('android-v2rayng',    `v2rayng://install-config?url=${u}`);
				set('ios-singbox',        `sing-box://import-remote-profile?url=${u}#${encodeURIComponent(this.clientName)}`);
				set('android-singbox',    `sing-box://import-remote-profile?url=${u}#${encodeURIComponent(this.clientName)}`);
				set('android-nekobox',    `sn://subscription?url=${u}&name=${encodeURIComponent(this.clientName)}`);
				set('android-hiddify',    `hiddify://import?url=${u}`);
				set('windows-clashverge', `clash://install-config?url=${u}`);
			},

			renderConfigs(){
				const list = document.getElementById('cfgList');
				list.innerHTML = '';
				(this.configs||[]).forEach((raw, idx)=>{
					const name = this.extractName(raw) || `Config ${idx+1}`;
					const row = document.createElement('div');
					row.className = 'flex flex-col sm:flex-row items-start sm:items-center gap-3 p-3 rounded-xl bg-white/70 border border-white/60';
					row.innerHTML = `
					<div class="flex-1 font-medium truncate w-full">${this.escapeHtml(name)}</div>
					<div class="flex items-center gap-2 w-full sm:w-auto">
						<button class="btn btn-dark btnCopy w-full sm:w-auto justify-center" title="کپی کانفیگ">
							<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
							کپی
						</button>
					</div>`;
					row.querySelector('.btnCopy').addEventListener('click', (e)=> { this.copyToClipboard(e, raw); this.toast('کانفیگ کپی شد','info'); });
					list.appendChild(row);
				});
			},

			copyAll(evt){ this.copyToClipboard(evt, (this.configs||[]).join('\n')); this.toast('همهٔ کانفیگ‌ها کپی شد','info'); },
			async copyToClipboard(evt, text){
				const useFallback = async () => { const ta=document.createElement('textarea'); ta.value=text; document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); } finally{ document.body.removeChild(ta); } };
				try{ await navigator.clipboard.writeText(text); } catch(e){ await useFallback(); }
				if(evt){ const btn = evt.target.closest('button'); btn.classList.add('ring','ring-amber-400','scale-95'); setTimeout(()=> btn.classList.remove('ring','ring-amber-400','scale-95'), 220); }
			},

			openQrImage(){ if(!this.qrMain) return; this.qr = { open:true, image:this.qrMain }; },

			extractName(url){ const m=/#([^#]*)/.exec(url); if(m) return decodeURIComponent(m[1]); if(url.startsWith('vmess://')){ try{ const obj = JSON.parse(atob(url.replace('vmess://',''))); return obj.ps || null; }catch(e){ return null; } } return null; },
			escapeHtml(s){ return String(s).replace(/[&<>"']/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"})[c]); },

			toast(msg, type='info'){
				const id = Date.now().toString(36)+Math.random().toString(36).slice(2);
				this.toasts.push({ id, msg, type });
				setTimeout(()=> this.dismissToast(id), 5000);
			},
			dismissToast(id){ this.toasts = this.toasts.filter(t=>t.id!==id); }
		}
	}
</script>
</body>
</html>
