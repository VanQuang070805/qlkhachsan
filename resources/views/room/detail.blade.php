@extends('layouts.main')

@section('content')
<?php $pageTitle = 'Chi Tiết - ' . htmlspecialchars($room['type_name'] ?? 'Phòng'); ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

:root {
    --gold:      #2597d0;
    --gold-dark: #386b97;
    --ink:       #070709;
    --cream:     #ffffff;
    --muted:     #7A7A8A;
    --border:    #e5e8ed;
    --green:     #2A7A4F;
}
body { font-family: 'Inter', sans-serif; background: var(--cream); }

/* ── Breadcrumb ── */
.bc { font-size: .85rem; margin-bottom: 1.5rem; color: var(--muted); }
.bc a { color: var(--ink); text-decoration: none; }
.bc a:hover { text-decoration: underline; }
.bc .sep { margin: 0 .4rem; color: var(--muted); }

/* ══════════════════════════════════════════
   LEFT COLUMN
══════════════════════════════════════════ */
.detail-img {
    width: 100%;
    height: 320px;
    object-fit: cover;
    border-radius: 16px;
    display: block;
    margin-bottom: 1.8rem;
    box-shadow: 0 8px 32px rgba(26,26,46,.13);
}
.room-title {
    font-family: 'Inter', sans-serif;
    font-size: 2.1rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: .9rem;
}
.info-line {
    display: flex; align-items: center; gap: 8px;
    font-size: .92rem; color: #333;
    margin-bottom: .4rem;
}
.info-line i { color: var(--gold); width: 18px; flex-shrink: 0; }
.block-heading {
    font-family: 'Inter', sans-serif;
    font-weight: 700; font-size: .97rem;
    color: var(--ink);
    margin: 1.3rem 0 .45rem;
}
.desc-text { font-size: .9rem; color: #555; line-height: 1.7; margin: 0; }

/* amenities grid 2-col */
.am-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    row-gap: 2px; column-gap: 0;
}
.am-row {
    display: flex; align-items: center; gap: 7px;
    font-size: .88rem; color: #333;
    padding: 5px 0;
}
.am-row i { color: var(--green); font-size: .9rem; flex-shrink: 0; }

/* ══════════════════════════════════════════
   RIGHT COLUMN — Booking panel + Room selector
══════════════════════════════════════════ */
.right-sticky { position: sticky; top: 80px; }

/* ── Booking card ── */
.bk-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--border);
    box-shadow: 0 4px 20px rgba(26,26,46,.08);
    overflow: hidden;
    margin-bottom: 1.2rem;
}
.bk-header {
    background: var(--ink);
    color: #fff;
    padding: .9rem 1.4rem;
    display: flex; align-items: center; gap: 9px;
    font-family: 'Inter', sans-serif;
    font-weight: 600; font-size: 1rem;
}
.bk-body { padding: 1.3rem 1.4rem 1.4rem; }

.f-label {
    font-size: .65rem; font-weight: 600;
    letter-spacing: .13em; text-transform: uppercase;
    color: var(--muted); margin-bottom: 5px; display: block;
}
.f-input {
    width: 100%;
    border: 1.5px solid var(--border);
    border-radius: 9px;
    padding: 9px 12px;
    font-family: 'Inter', sans-serif;
    font-size: .9rem; color: var(--ink);
    background: var(--cream);
    transition: border-color .2s, box-shadow .2s;
    appearance: auto;
}
.f-input:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(37,151,208,.12);
    background: #fff;
}
.f-input[readonly] { cursor: default; background: #f5f5f5; }

/* ── Thêm từ detail.php: validate states ── */
.f-input.is-invalid {
    border-color: #C0392B !important;
    box-shadow: 0 0 0 3px rgba(192,57,43,.1) !important;
}
.f-input.is-valid {
    border-color: var(--green) !important;
    box-shadow: 0 0 0 3px rgba(42,122,79,.1) !important;
}
.date-error {
    font-size: .75rem;
    color: #C0392B;
    margin-top: 3px;
    min-height: 16px;
    display: flex;
    align-items: center;
    gap: 3px;
}

.btn-book {
    width: 100%;
    background: var(--ink);
    color: #fff; border: none;
    border-radius: 11px;
    padding: .82rem;
    font-family: 'Inter', sans-serif;
    font-weight: 600; font-size: .96rem;
    letter-spacing: .03em;
    cursor: pointer;
    transition: opacity .18s, transform .15s;
    margin-top: .3rem;
    display: block;
    text-align: center;
    text-decoration: none;
}
.btn-book:hover:not(:disabled):not([style*="pointer-events: none"]) {
    opacity: .88; transform: translateY(-1px);
}
.btn-book:disabled { opacity: .5; cursor: not-allowed; }

.bk-hint {
    text-align: center;
    font-size: .77rem; color: var(--muted);
    margin-top: .55rem; margin-bottom: 0;
}

/* ── Room selector card ── */
.rs-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid var(--border);
    box-shadow: 0 4px 20px rgba(26,26,46,.08);
    overflow: hidden;
}
.rs-header {
    background: var(--ink); color: #fff;
    padding: .9rem 1.4rem;
    display: flex; align-items: center; gap: 9px;
    font-family: 'Inter', sans-serif;
    font-weight: 600; font-size: 1rem;
}
.rs-body { padding: 1.1rem 1.4rem 1.3rem; }

/* Legend */
.rs-legend {
    display: flex; gap: 1rem; flex-wrap: wrap;
    margin-bottom: .9rem;
    font-size: .78rem; color: #555;
    font-family: 'Inter', sans-serif;
}
.rs-legend-item { display: flex; align-items: center; gap: 5px; }
.rs-dot {
    width: 13px; height: 13px; border-radius: 3px; border: 1.5px solid;
    flex-shrink: 0;
}
.rs-dot.avail  { background: #EEF2FF; border-color: #C5CFE8; }
.rs-dot.sel    { background: var(--ink); border-color: var(--ink); }
.rs-dot.taken  { background: #e9ecef; border-color: #ced4da; }

/* floor label */
.floor-lbl {
    font-size: .65rem; font-weight: 700;
    letter-spacing: .14em; text-transform: uppercase;
    color: var(--muted); margin: .85rem 0 .4rem;
}
.floor-lbl:first-of-type { margin-top: 0; }
.floor-rooms { display: flex; flex-wrap: wrap; gap: 0; }

/* room button */
.rm-btn {
    display: inline-flex; flex-direction: column;
    align-items: center; justify-content: center;
    width: 64px; height: 56px;
    border-radius: 9px;
    border: 1.5px solid #C5CFE8;
    background: #EEF2FF;
    color: var(--ink);
    font-family: 'Inter', sans-serif;
    font-weight: 700; font-size: .88rem;
    cursor: pointer;
    transition: all .17s;
    margin: 3px;
}
.rm-btn .rm-sub { font-size: .57rem; font-weight: 400; opacity: .65; margin-top: 1px; }
.rm-btn:hover:not(.taken) {
    border-color: var(--ink); background: var(--ink); color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(26,26,46,.2);
}
.rm-btn.selected {
    border-color: var(--ink); background: var(--ink); color: #fff;
    box-shadow: 0 4px 12px rgba(26,26,46,.2);
}
.rm-btn.taken {
    background: #e9ecef; border-color: #ced4da;
    color: #adb5bd; cursor: not-allowed; opacity: .65;
}

.rs-empty { font-size: .87rem; color: var(--muted); padding: .3rem 0; }

/* ── Thêm từ detail.php: Booking bar dính dưới ── */
.bk-bar {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 300;
    background: var(--ink);
    color: #fff;
    padding: 12px 24px;
    display: none;
    border-top: 3px solid var(--gold);
    box-shadow: 0 -4px 20px rgba(0,0,0,.18);
}
.bk-bar.visible {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.bk-bar-labels { display: flex; flex-wrap: wrap; gap: 6px; }
.bk-label-badge {
    background: rgba(255,255,255,.15);
    border-radius: 20px;
    padding: 3px 10px;
    font-size: .8rem;
    font-weight: 600;
}
.bk-capacity.ok   { color: #6ee7a0; font-size: .82rem; }
.bk-capacity.warn { color: #fbbf24; font-size: .82rem; }
.btn-bk-submit {
    background: linear-gradient(135deg, var(--gold-dark), var(--gold));
    color: #fff; border: none;
    border-radius: 9px;
    padding: 9px 24px;
    font-weight: 700; font-size: .93rem;
    cursor: pointer;
    transition: opacity .18s, transform .15s;
    white-space: nowrap;
}
.btn-bk-submit:disabled { opacity: .45; cursor: not-allowed; }
.btn-bk-submit:not(:disabled):hover { opacity: .88; transform: translateY(-1px); }
.btn-bk-clear {
    background: rgba(255,255,255,.12);
    color: #fff; border: none;
    border-radius: 9px;
    padding: 9px 16px;
    font-size: .85rem; cursor: pointer;
    transition: background .15s;
}
.btn-bk-clear:hover { background: rgba(255,255,255,.22); }

/* Royal guest-room experience */
.room-detail-gallery { margin-bottom:1.65rem; }
.room-detail-gallery .contact-gallery__viewport { min-height:clamp(360px,46vw,520px); border:1px solid rgba(119,155,193,.18); border-radius:22px; box-shadow:0 22px 54px rgba(31,70,109,.11); }
.room-detail-gallery .contact-gallery__controls { padding-inline:4px; }
.room-title { margin-top:.35rem; font-size:clamp(2rem,4vw,3.2rem); line-height:1; letter-spacing:-.05em; }
.right-sticky { top:112px; }
.bk-card,.rs-card { border:1px solid rgba(119,155,193,.2); border-radius:22px; box-shadow:0 18px 48px rgba(31,70,109,.09); transition:transform .3s ease,box-shadow .3s ease,border-color .3s ease; }
.bk-card:hover,.rs-card:hover { border-color:rgba(56,107,151,.36); box-shadow:0 24px 58px rgba(31,70,109,.13); }
.bk-header,.rs-header { min-height:58px; padding:1rem 1.35rem; border-bottom:1px solid #e4ebf1; background:linear-gradient(135deg,#f4f8fb,#e7f0f7); color:#151515; font-size:.92rem; letter-spacing:-.01em; }
.bk-header i,.rs-header i { display:grid; width:32px; height:32px; place-items:center; border-radius:10px; background:#fff; color:#386b97; box-shadow:0 5px 16px rgba(31,70,109,.08); }
.bk-body,.rs-body { padding:1.25rem; }
.f-input { min-height:45px; border:1px solid #dfe6ec; border-radius:11px; background:#fff; }
.f-input:hover { border-color:#b6c9d9; }
.btn-book { min-height:49px; border-radius:999px; letter-spacing:0; box-shadow:0 10px 24px rgba(7,7,9,.14); transition:transform .25s ease,box-shadow .25s ease,background-color .25s ease; }
.btn-book:hover:not(:disabled):not([style*="pointer-events: none"]) { transform:translateY(-2px); background:#24272d; box-shadow:0 15px 30px rgba(7,7,9,.2); opacity:1; }
.rs-legend { padding:10px 12px; border-radius:12px; background:#f7f9fb; }
.floor-lbl { margin-top:1rem; color:#657382; }
.floor-rooms { gap:6px; }
.rm-btn { width:68px; height:58px; margin:0; border:1px solid #cfdae4; border-radius:13px; background:#f4f8fb; transition:transform .25s ease,background-color .25s ease,border-color .25s ease,box-shadow .25s ease; }
.rm-btn:hover:not(.taken) { transform:translateY(-3px) scale(1.03); }
.rm-btn.selected { transform:translateY(-2px); box-shadow:0 9px 22px rgba(7,7,9,.18); }
.info-line { padding:10px 12px; margin-bottom:8px; border-radius:12px; background:#f7f9fb; }
.block-heading { margin-top:1.7rem; }
.room-detail-intro { position:relative; isolation:isolate; overflow:hidden; margin:-20px 0 28px; padding:clamp(38px,6vw,68px); border:1px solid rgba(255,255,255,.82); border-radius:24px; background:linear-gradient(135deg,#779bc1 0%,#9abfda 58%,#cbdcec 100%); color:#fff; box-shadow:0 24px 60px rgba(31,70,109,.12); }
.room-detail-intro::after { content:""; position:absolute; z-index:-1; width:360px; aspect-ratio:1; right:-90px; top:-170px; border-radius:50%; background:radial-gradient(circle,rgba(255,255,255,.32),transparent 68%); }
.room-detail-intro__eyebrow { margin:0 0 12px; color:rgba(255,255,255,.86); font-size:.68rem; font-weight:700; letter-spacing:.16em; text-transform:uppercase; }
.room-detail-intro h1 { max-width:760px; margin:0; color:#fff; font-size:clamp(2.7rem,6vw,5rem); font-weight:600; line-height:.94; letter-spacing:-.06em; }
.room-detail-intro__meta { display:flex; flex-wrap:wrap; gap:10px; margin-top:24px; }
.room-detail-intro__meta span { display:inline-flex; align-items:center; gap:8px; min-height:38px; padding:0 15px; border:1px solid rgba(255,255,255,.38); border-radius:999px; background:rgba(255,255,255,.13); color:#fff; font-size:.78rem; backdrop-filter:blur(10px); }
.room-detail-content { padding:4px; }
.room-detail-content>.col-lg-7 { padding:clamp(8px,2vw,18px); border-radius:24px; background:linear-gradient(180deg,#f7fbfe 0,#fff 38%); }
.room-title { font-size:clamp(1.9rem,3vw,2.8rem); }
.bk-card,.rs-card { overflow:visible; background:rgba(255,255,255,.94); backdrop-filter:blur(18px); }
.bk-header,.rs-header { border-radius:21px 21px 0 0; }
.bk-body { display:grid; gap:10px; }
.rs-body { max-height:440px; overflow:auto; scrollbar-width:thin; scrollbar-color:#a9bfd2 transparent; }
.rm-btn { position:relative; overflow:hidden; }
.rm-btn::after { content:""; position:absolute; width:38px; height:38px; top:-28px; right:-28px; border-radius:50%; background:rgba(255,255,255,.72); transition:transform .35s ease; }
.rm-btn:hover::after { transform:scale(3); }
.rm-btn.selected { border-color:#070709; background:#070709; color:#fff; }
.review-item { border-radius:16px!important; box-shadow:0 12px 32px rgba(31,70,109,.06)!important; }

/* Editorial room journey — image first, concise information, clear reservation flow. */
.room-detail-intro { min-height:clamp(410px,54vw,610px); display:flex; flex-direction:column; justify-content:flex-end; margin-top:-28px; padding:clamp(48px,7vw,82px); border-radius:30px; }
.room-detail-intro__topline { display:flex; align-items:center; justify-content:space-between; gap:18px; margin-bottom:auto; }
.room-detail-intro__index { color:rgba(255,255,255,.72); font-size:.7rem; letter-spacing:.16em; text-transform:uppercase; }
.room-detail-intro h1 { max-width:900px; text-wrap:balance; }
.room-detail-intro__footer { display:flex; align-items:flex-end; justify-content:space-between; gap:28px; }
.room-detail-intro__price { flex:0 0 auto; padding:14px 18px; border:1px solid rgba(255,255,255,.48); border-radius:18px; background:rgba(255,255,255,.13); backdrop-filter:blur(14px); }
.room-detail-intro__price small,.room-detail-intro__price span { display:block; color:rgba(255,255,255,.76); font-size:.68rem; }
.room-detail-intro__price strong { display:block; color:#fff; font-size:clamp(1.15rem,2vw,1.55rem); font-weight:600; }
.room-detail-nav { position:sticky; top:104px; z-index:30; display:flex; align-items:center; gap:4px; width:max-content; max-width:100%; margin:0 auto 34px; padding:6px; overflow:auto; border:1px solid rgba(119,155,193,.2); border-radius:999px; background:rgba(255,255,255,.78); box-shadow:0 12px 34px rgba(31,70,109,.09); backdrop-filter:blur(18px); }
.room-detail-nav a { padding:9px 15px; border-radius:999px; color:#596573; font-size:.76rem; font-weight:600; text-decoration:none; white-space:nowrap; }
.room-detail-nav a:hover,.room-detail-nav a.is-active { background:#070709; color:#fff; }
.room-detail-content { --bs-gutter-x:2.25rem; }
.room-detail-content>.col-lg-7 { padding:0; background:#fff; }
.room-detail-gallery { position:relative; }
.room-detail-gallery::before { content:"Album phòng"; position:absolute; z-index:5; top:18px; left:18px; padding:8px 12px; border:1px solid rgba(255,255,255,.7); border-radius:999px; background:rgba(255,255,255,.76); color:#151515; font-size:.68rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; backdrop-filter:blur(14px); }
.room-detail-gallery .contact-gallery__viewport { min-height:clamp(430px,50vw,610px); border-radius:28px; }
.room-story { margin:clamp(48px,6vw,78px) 0 0; }
.room-story__heading { display:grid; grid-template-columns:minmax(0,.82fr) minmax(0,1.18fr); gap:clamp(28px,5vw,72px); align-items:start; }
.room-story__eyebrow { display:flex; align-items:center; gap:9px; margin:0 0 14px; color:#386b97; font-size:.68rem; font-weight:700; letter-spacing:.15em; text-transform:uppercase; }
.room-story__eyebrow::before { content:""; width:7px; height:7px; border-radius:50%; background:#2597d0; }
.room-story h2 { margin:0; color:#070709; font-size:clamp(2.15rem,4.2vw,3.8rem); font-weight:600; line-height:.98; letter-spacing:-.055em; text-wrap:balance; }
.room-story__copy { margin:3px 0 0; color:#616a76; font-size:clamp(.94rem,1.3vw,1.05rem); line-height:1.75; }
.room-facts { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin:30px 0 0; }
.room-fact { min-height:118px; padding:19px; border:1px solid #e5ebf0; border-radius:18px; background:#f8fafc; }
.room-fact i { display:grid; width:34px; height:34px; margin-bottom:18px; place-items:center; border-radius:11px; background:#e7f1f8; color:#386b97; }
.room-fact small { display:block; color:#7a8490; font-size:.65rem; letter-spacing:.1em; text-transform:uppercase; }
.room-fact strong { display:block; margin-top:4px; color:#151515; font-size:.9rem; font-weight:600; }
.room-amenities { margin-top:46px; padding:clamp(25px,4vw,38px); border-radius:24px; background:linear-gradient(145deg,#edf5fa,#f8fbfd); }
.room-amenities__head { display:flex; justify-content:space-between; gap:20px; align-items:end; margin-bottom:22px; }
.room-amenities__head h3,.room-reviews__head h3 { margin:0; color:#070709; font-size:clamp(1.45rem,2.5vw,2.15rem); font-weight:600; letter-spacing:-.035em; }
.room-amenities__head span { color:#77818c; font-size:.75rem; }
.am-grid { gap:8px; }
.am-row { min-height:44px; padding:9px 12px; border:1px solid rgba(119,155,193,.14); border-radius:12px; background:rgba(255,255,255,.72); }
.room-reviews { margin-top:54px; }
.room-reviews__head { display:flex; align-items:end; justify-content:space-between; gap:20px; margin-bottom:20px; padding-bottom:18px; border-bottom:1px solid #e8edf1; }
.room-reviews__score { color:#386b97; font-size:.86rem; font-weight:600; }
.reviews-list { display:grid; gap:12px; }
.review-item { margin:0!important; padding:20px!important; border:1px solid #e8edf1!important; box-shadow:none!important; }
.right-sticky { top:116px; display:grid; gap:16px; }
.bk-card,.rs-card { margin:0; border-radius:26px; }
.bk-card { border-color:rgba(255,255,255,.8); background:linear-gradient(155deg,#e7f2f9 0%,#fff 45%); box-shadow:0 24px 60px rgba(31,70,109,.13); }
.bk-header,.rs-header { min-height:auto; padding:20px 22px 10px; border:0; border-radius:26px 26px 0 0; background:transparent; }
.bk-header { flex-wrap:wrap; }
.booking-kicker { width:100%; color:#788591; font-size:.64rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase; }
.booking-title { display:flex; align-items:center; gap:10px; color:#101114; font-size:1.18rem; font-weight:600; }
.booking-rate { display:flex; align-items:baseline; gap:5px; width:100%; margin-top:12px; }
.booking-rate strong { color:#070709; font-size:clamp(1.65rem,3vw,2.15rem); font-weight:600; letter-spacing:-.04em; }
.booking-rate span { color:#697581; font-size:.76rem; }
.bk-body,.rs-body { padding:16px 22px 22px; }
.f-label { color:#6c7782; }
.f-input { min-height:48px; background:rgba(255,255,255,.9); }
.rs-card { background:#fff; }
.rs-header { padding-bottom:15px; border-bottom:1px solid #e8edf1; }
.reservation-steps { display:grid; grid-template-columns:repeat(3,1fr); gap:7px; margin-bottom:16px; }
.reservation-steps span { display:flex; align-items:center; gap:6px; color:#7a8490; font-size:.65rem; }
.reservation-steps b { display:grid; width:20px; height:20px; place-items:center; border-radius:50%; background:#e7f1f8; color:#386b97; font-size:.6rem; }

/* Taste audit: open editorial rhythm, one radius system, fewer decorative boxes. */
.room-detail-intro__topline { justify-content:flex-start; }
.room-detail-intro__meta span { min-height:auto; padding:0; border:0; border-radius:0; background:transparent; backdrop-filter:none; }
.room-detail-intro__meta span+span::before { content:""; width:1px; height:18px; margin-right:2px; background:rgba(255,255,255,.36); }
.room-detail-gallery::before { display:none; }
.room-metrics { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); margin:38px 0 0; padding:24px 0; border-block:1px solid #e5ebf0; }
.room-metric { min-width:0; padding:0 22px; }
.room-metric:first-child { padding-left:0; }
.room-metric+.room-metric { border-left:1px solid #e5ebf0; }
.room-metric dt { color:#7a8490; font-size:.65rem; font-weight:600; letter-spacing:.11em; text-transform:uppercase; }
.room-metric dd { margin:7px 0 0; color:#151515; font-size:clamp(.9rem,1.5vw,1.05rem); font-weight:600; }
.room-amenities { margin-top:58px; padding:0; border-radius:0; background:transparent; }
.am-row { min-height:48px; padding:10px 0; border:0; border-bottom:1px solid #e8edf1; border-radius:0; background:transparent; }
.bk-card,.rs-card { border-radius:24px; }

/* Simplified room detail composition. */
.room-detail-heading { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:end; gap:32px; padding:clamp(46px,6vw,76px) 0 clamp(34px,5vw,54px); }
.room-detail-breadcrumb { display:flex; align-items:center; gap:8px; margin-bottom:24px; color:#7a8490; font-size:.76rem; }
.room-detail-breadcrumb a { color:#596573; text-decoration:none; }
.room-detail-breadcrumb a:hover { color:#070709; }
.room-detail-breadcrumb i { font-size:.62rem; }
.room-detail-heading__eyebrow { margin:0 0 13px; color:#386b97; font-size:.68rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase; }
.room-detail-heading h1 { max-width:780px; margin:0; color:#070709; font-size:clamp(2.8rem,5.7vw,5rem); font-weight:600; line-height:.94; letter-spacing:-.055em; text-wrap:balance; }
.room-detail-heading__aside { min-width:210px; padding-bottom:5px; text-align:right; }
.room-detail-heading__aside strong { display:block; color:#070709; font-size:clamp(1.25rem,2.2vw,1.8rem); font-weight:600; letter-spacing:-.035em; }
.room-detail-heading__aside span { display:block; margin-top:7px; color:#707985; font-size:.78rem; }
.room-detail-heading__rating { display:flex; justify-content:flex-end; align-items:center; gap:6px; margin-bottom:16px; color:#386b97; font-size:.78rem; font-weight:600; }
.room-detail-heading__rating small { color:#7a8490; font-weight:400; }
.room-detail-gallery { margin:0 0 clamp(52px,7vw,84px); }
.room-detail-gallery .contact-gallery__viewport { min-height:clamp(520px,67vw,760px); border-radius:24px; box-shadow:none; }
.room-detail-gallery .contact-gallery__controls { padding:14px 2px 0; }
.room-detail-content { --bs-gutter-x:clamp(28px,5vw,68px); padding:0; }
.legacy-main:has(.room-detail-heading) { border:0; border-radius:0; background:linear-gradient(180deg,#779bc1 0,#9abfda 116px,#cbdcec 168px,#f9f9f9 236px); }
.room-detail-content>.col-lg-7 { padding:clamp(25px,4vw,42px); border:1px solid #e8ecef; border-radius:24px; background:#fff; }
.room-story { margin:0; }
.room-story__heading { display:block; }
.room-story h2 { max-width:520px; font-size:clamp(2.1rem,4vw,3.4rem); }
.room-story__copy { max-width:660px; margin-top:24px; }
.room-metrics { margin-top:34px; }
.room-amenities { margin-top:46px; }
.right-sticky { top:112px; }
.bk-card { background:#f4f8fb; box-shadow:none; border-color:#e2ebf2; }
.bk-header { padding-bottom:0; }
.booking-title { font-size:1.05rem; }
.booking-rate { margin-top:8px; }
.rs-card { box-shadow:none; border-color:#e5e8ed; }
.room-reviews { margin-top:64px; }
.booking-estimate { display:grid; gap:9px; margin-top:4px; padding-top:14px; border-top:1px solid #dce6ed; }
.booking-estimate__row { display:flex; justify-content:space-between; gap:18px; color:#6d7782; font-size:.78rem; }
.booking-estimate__row strong { color:#333b43; font-weight:600; }
.booking-estimate__total { align-items:end; margin-top:5px; padding:14px; border-radius:14px; background:#fff; color:#151515; }
.booking-estimate__total span { font-weight:600; }
.booking-estimate__total strong { color:#070709; font-size:1.05rem; }
.booking-estimate--final { margin:16px 0 14px; }
@media(max-width:760px){.room-detail-heading{grid-template-columns:1fr;padding-top:70px}.room-detail-heading__aside{min-width:0;text-align:left}.room-detail-heading__rating{justify-content:flex-start}.room-detail-heading h1{font-size:clamp(2.8rem,15vw,4.6rem)}.room-detail-gallery{margin-bottom:48px}.room-detail-content>.col-lg-7{padding:22px 18px}}
@media(max-width:991px){.room-detail-nav{top:92px}.room-detail-content{--bs-gutter-x:1.5rem}.room-story__heading{grid-template-columns:1fr}.room-detail-intro__footer{align-items:flex-start;flex-direction:column}.room-detail-gallery .contact-gallery__viewport{min-height:440px}}
@media(max-width:600px){.room-detail-intro{min-height:470px;padding:38px 24px}.room-detail-intro__topline{align-items:flex-start;flex-direction:column}.room-detail-intro__meta{gap:10px}.room-detail-intro__meta span{padding-inline:0}.room-detail-intro__meta span+span::before{display:none}.room-detail-nav{justify-content:flex-start;width:100%;top:82px}.room-detail-gallery .contact-gallery__viewport{min-height:340px;border-radius:22px}.room-metrics{grid-template-columns:1fr;gap:16px}.room-metric{padding:0}.room-metric+.room-metric{padding-top:16px;border-top:1px solid #e5ebf0;border-left:0}.room-story{margin-top:42px}.room-amenities{padding:0}.room-amenities__head,.room-reviews__head{align-items:flex-start;flex-direction:column}.am-grid{grid-template-columns:1fr}.reservation-steps{grid-template-columns:repeat(3,1fr)}.reservation-steps span{display:grid;justify-items:center;text-align:center}.right-sticky{gap:12px}}
@media(max-width:991px) { .right-sticky { position:static; } .room-detail-gallery .contact-gallery__viewport { min-height:420px; } }
@media(max-width:600px) { .room-detail-gallery .contact-gallery__viewport { min-height:320px; } .bk-card,.rs-card { border-radius:18px; } }

/* Demo-aligned editorial layout: compact intro, asymmetric album and 8/4 content grid. */
.room-detail-heading { display:block; padding:clamp(30px,4vw,52px) 0 24px; }
.room-detail-breadcrumb { margin-bottom:18px; }
.room-detail-heading__topline { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:14px; }
.room-detail-heading__pill { display:inline-flex; align-items:center; gap:8px; min-height:36px; padding:0 14px; border-radius:999px; background:#f1f3f5; color:#505963; font-size:.7rem; font-weight:700; letter-spacing:.11em; text-transform:uppercase; }
.room-detail-heading__pill::before { width:7px; height:7px; border-radius:50%; background:#2597d0; content:""; box-shadow:0 0 0 5px rgba(37,151,208,.09); }
.room-detail-heading__rating { justify-content:flex-start; min-height:36px; margin:0; padding:0 14px; border:1px solid #edf0f2; border-radius:999px; background:#fff; color:#17191c; }
.room-detail-heading__main { display:block; }
.room-detail-heading h1 { max-width:850px; font-size:clamp(2.65rem,5vw,4.4rem); line-height:1; }
.room-detail-heading__description { max-width:760px; margin:16px 0 0; color:#66717d; font-size:clamp(.96rem,1.5vw,1.12rem); line-height:1.65; }
.room-detail-heading__aside { min-width:230px; padding:0 0 4px; }
.room-detail-heading__aside strong { font-size:clamp(1.35rem,2vw,1.75rem); }

.room-demo-gallery { position:relative; display:grid; grid-template-columns:minmax(0,2fr) minmax(260px,1fr); gap:16px; margin:18px 0 clamp(42px,6vw,70px); }
.room-demo-gallery__main,.room-demo-gallery__thumb { position:relative; overflow:hidden; border:0; border-radius:20px; background:#e9edf0; padding:0; }
.room-demo-gallery__main { min-height:clamp(430px,48vw,620px); }
.room-demo-gallery__rail { display:grid; grid-template-rows:1fr 1fr; gap:16px; min-height:clamp(430px,48vw,620px); }
.room-demo-gallery img { width:100%; height:100%; object-fit:cover; transition:transform .7s cubic-bezier(.2,.75,.2,1),opacity .25s ease; }
.room-demo-gallery__main:hover img,.room-demo-gallery__thumb:hover img { transform:scale(1.025); }
.room-demo-gallery__label { position:absolute; left:16px; bottom:16px; display:inline-flex; align-items:center; gap:8px; padding:9px 13px; border:1px solid rgba(255,255,255,.62); border-radius:999px; background:rgba(255,255,255,.82); color:#17191c; font-size:.72rem; font-weight:600; backdrop-filter:blur(14px); }
.room-demo-gallery__count { position:absolute; right:18px; bottom:18px; z-index:3; display:inline-flex; align-items:center; gap:8px; padding:11px 15px; border:1px solid rgba(255,255,255,.7); border-radius:999px; background:rgba(255,255,255,.9); color:#111; font-size:.78rem; font-weight:600; box-shadow:0 12px 30px rgba(16,44,70,.13); }
.room-demo-gallery__thumb { cursor:pointer; }
.room-demo-gallery__thumb:focus-visible { outline:3px solid rgba(37,151,208,.4); outline-offset:3px; }

.room-detail-content { --bs-gutter-x:24px; }
.room-detail-content>.col-lg-7 { width:66.66666667%; padding:0; border:0; border-radius:0; background:transparent; }
.room-detail-content>.col-lg-5 { width:33.33333333%; }
.room-story,.room-amenities,.room-reviews { padding:clamp(24px,3.4vw,40px); border:1px solid #eceff1; border-radius:20px; background:#fff; }
.room-story__heading { display:block; }
.room-story h2 { font-size:clamp(1.9rem,3.3vw,2.8rem); }
.room-story__copy { margin-top:18px; }
.room-metrics { padding:18px; border:0; border-radius:16px; background:#f4f5f6; }
.room-amenities { margin-top:24px; }
.room-reviews { margin-top:24px; }
.right-sticky { top:108px; gap:18px; }
.bk-card,.rs-card { border-radius:20px; }
.bk-card { background:#fff; border-color:#e7ebee; }
@media(max-width:991px){.room-detail-heading__main{grid-template-columns:1fr}.room-detail-heading__aside{text-align:left}.room-demo-gallery{grid-template-columns:1fr}.room-demo-gallery__rail{grid-template-columns:1fr 1fr;grid-template-rows:none;min-height:220px}.room-detail-content>.col-lg-7,.room-detail-content>.col-lg-5{width:100%}}
@media(max-width:600px){.room-demo-gallery__main{min-height:360px}.room-demo-gallery__rail{min-height:170px;gap:10px}.room-demo-gallery{gap:10px}.room-demo-gallery__label{display:none}.room-story,.room-amenities,.room-reviews{padding:22px 18px}}
</style>

<section class="room-detail-heading" aria-labelledby="room-detail-title" data-reveal>
    <nav class="room-detail-breadcrumb" aria-label="Đường dẫn trang">
        <a href="{{ route('home') }}">Trang chủ</a><i class="bi bi-chevron-right" aria-hidden="true"></i>
        <a href="{{ route('rooms.index') }}">Phòng nghỉ</a><i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span><?= htmlspecialchars($room['type_name'] ?? 'Phòng Royal') ?></span>
    </nav>
    <div class="room-detail-heading__topline">
        <span class="room-detail-heading__pill">Royal private stay</span>
        <div class="room-detail-heading__rating"><i class="bi bi-star-fill" aria-hidden="true"></i><?= $room->averageRating() > 0 ? number_format($room->averageRating(), 1) : 'Mới' ?><small><?= $room->reviews()->count() ? '(' . $room->reviews()->count() . ' đánh giá)' : '(chưa có đánh giá)' ?></small></div>
    </div>
    <div class="room-detail-heading__main">
        <div>
            <h1 id="room-detail-title"><?= htmlspecialchars($room['type_name'] ?? 'Phòng Royal') ?></h1>
            <p class="room-detail-heading__description"><?= htmlspecialchars($room['description'] ?? 'Một không gian nghỉ dưỡng riêng tư, thanh lịch và được chăm chút cho từng nhịp nghỉ.') ?></p>
        </div>
    </div>
</section>

<?php
$roomGallery = config('room_images.' . $room['id'], array_filter([$room['image'] ?? null]));
$heroImg = $room['image'] ?? ($roomGallery[0] ?? url('/') . '/images/rooms/default.jpg');
$imgFallback = url('/') . '/images/rooms/default.jpg';

$adults      = max(1, (int)($_GET['adults'] ?? 1));
$children    = max(0, (int)($_GET['children'] ?? 0));
$maxAdults   = (int)($room['max_adults'] ?? 1);
$maxChildren = (int)($room['max_children'] ?? 0);
$maxGuests   = (int)($room['max_guests'] ?? 1);

// Calculate rooms needed dynamically
$isInsufficient = ($maxAdults < $adults || $maxChildren < $children || $maxGuests < ($adults + $children));

$roomsNeeded = 1;
if ($isInsufficient) {
    $roomsNeeded = 999;
} else {
    while (true) {
        $totalMaxAdults = $roomsNeeded * $maxAdults;
        $totalMaxChildren = $roomsNeeded * $maxChildren;
        $totalMaxGuests = $roomsNeeded * $maxGuests;
        if ($totalMaxAdults >= $adults && $totalMaxChildren >= $children && $totalMaxGuests >= ($adults + $children)) {
            break;
        }
        $roomsNeeded++;
        if ($roomsNeeded > 100) {
            $roomsNeeded = 999;
            break;
        }
    }
}
$multiRoomMode = $roomsNeeded > 1 && $roomsNeeded < 999;

// Group rooms by floor
$byFloor = [];
foreach (($allRoomsOfType ?? []) as $r) {
    $byFloor[(int)$r['floor']][] = $r;
}
ksort($byFloor);
?>

<?php
$galleryImages = array_values($roomGallery);
$galleryPrimary = $galleryImages[0] ?? $imgFallback;
$gallerySecond = $galleryImages[1] ?? $galleryPrimary;
$galleryThird = $galleryImages[2] ?? $gallerySecond;
?>
<section class="room-demo-gallery" id="gallery" data-reveal aria-label="Album <?= htmlspecialchars($room['type_name']) ?>">
    <figure class="room-demo-gallery__main">
        <img id="roomGalleryPrimary" src="<?= htmlspecialchars($galleryPrimary) ?>" onerror="this.onerror=null;this.src='<?= $imgFallback ?>'" alt="Không gian chính của <?= htmlspecialchars($room['type_name']) ?>" loading="eager">
        <figcaption class="room-demo-gallery__label"><i class="bi bi-brightness-high" aria-hidden="true"></i>Không gian nghỉ dưỡng Royal</figcaption>
    </figure>
    <div class="room-demo-gallery__rail" aria-label="Chọn góc nhìn phòng">
        <button class="room-demo-gallery__thumb" type="button" data-room-gallery-image="<?= htmlspecialchars($gallerySecond) ?>" aria-label="Xem góc phòng thứ hai">
            <img src="<?= htmlspecialchars($gallerySecond) ?>" onerror="this.onerror=null;this.src='<?= $imgFallback ?>'" alt="Góc nhìn thứ hai của <?= htmlspecialchars($room['type_name']) ?>" loading="lazy">
            <span class="room-demo-gallery__label">Không gian nghỉ</span>
        </button>
        <button class="room-demo-gallery__thumb" type="button" data-room-gallery-image="<?= htmlspecialchars($galleryThird) ?>" aria-label="Xem góc phòng thứ ba">
            <img src="<?= htmlspecialchars($galleryThird) ?>" onerror="this.onerror=null;this.src='<?= $imgFallback ?>'" alt="Góc nhìn thứ ba của <?= htmlspecialchars($room['type_name']) ?>" loading="lazy">
            <span class="room-demo-gallery__label">Chi tiết trong phòng</span>
        </button>
    </div>
    <span class="room-demo-gallery__count"><i class="bi bi-images" aria-hidden="true"></i><?= count($galleryImages) ?> ảnh trong album</span>
</section>

<div class="row g-4 align-items-start room-detail-content">

    <!-- ══ LEFT ══ -->
    <div class="col-lg-7">

        <section class="room-story" id="overview" data-room-story>
            <div class="room-story__heading">
                <div>
                    <p class="room-story__eyebrow">Room details</p>
                    <h2>At a glance</h2>
                </div>
                <p class="room-story__copy"><?= htmlspecialchars($room['description'] ?? '') ?></p>
            </div>

            <dl class="room-metrics">
                <div class="room-metric"><dt>Sức chứa</dt><dd><?= $room['max_adults'] ?> người lớn · <?= $room['max_children'] ?> trẻ em</dd></div>
                <div class="room-metric"><dt>Mỗi đêm</dt><dd><?= number_format($room['price'], 0, ',', '.') ?> VNĐ</dd></div>
                <div class="room-metric"><dt>Đánh giá</dt><dd><?= $room->averageRating() > 0 ? number_format($room->averageRating(), 1) . ' / 5.0' : 'Chưa có đánh giá' ?></dd></div>
            </dl>
        </section>

        <?php if (!empty($room['amenities'])): ?>
        <section class="room-amenities" id="amenities" data-reveal>
            <div class="room-amenities__head">
                <h3>Room essentials</h3>
                <span><?= count($room['amenities']) ?> tiện nghi trong phòng</span>
            </div>
            <div class="am-grid">
                <?php foreach ($room['amenities'] as $a): ?>
                <div class="am-row">
                    <i class="bi bi-check2" aria-hidden="true"></i>
                    <?= htmlspecialchars($a['amenity_name']) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="room-reviews" id="reviews" data-reveal>
        <div class="room-reviews__head">
            <div>
                <p class="room-story__eyebrow">Guest notes</p>
                <h3>Guest notes</h3>
            </div>
            <div class="room-reviews__score">
            <?php
            $avgRating = $room->averageRating();
            $fullStars = floor($avgRating);
            $halfStar = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
            $emptyStars = 5 - $fullStars - $halfStar;
            for ($i = 0; $i < $fullStars; $i++) {
                echo '<i class="bi bi-star-fill" style="color: #2597d0;"></i>';
            }
            if ($halfStar) {
                echo '<i class="bi bi-star-half" style="color: #2597d0;"></i>';
            }
            for ($i = 0; $i < $emptyStars; $i++) {
                echo '<i class="bi bi-star" style="color: #ccc;"></i>';
            }
            if ($avgRating > 0) {
                echo ' <span>' . number_format($avgRating, 1) . ' · ' . $room->reviews()->count() . ' đánh giá</span>';
            } else {
                echo ' <span>Chưa có đánh giá</span>';
            }
            ?>
            </div>
        </div>
        <div class="reviews-list">
            <?php if ($room->reviews->isEmpty()): ?>
                <div class="p-3 mb-2 bg-white rounded shadow-sm border text-muted text-center" style="font-size: 0.9rem;">
                    Chưa có đánh giá nào cho loại phòng này.
                </div>
            <?php else: ?>
                <?php foreach ($room->reviews as $review): ?>
                <div class="review-item p-3 mb-3 bg-white rounded shadow-sm border border-light" style="font-family: 'Inter', sans-serif;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="text-dark" style="font-size: 0.9rem;"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($review->user->fullname ?? 'Khách hàng') ?></strong>
                        <span class="text-muted" style="font-size: 0.78rem;"><i class="bi bi-calendar-event me-1"></i><?= $review->created_at ? $review->created_at->format('d/m/Y') : '' ?></span>
                    </div>
                    <div class="mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star-fill" style="color: <?= $i <= $review->rating ? '#2597d0' : '#e4e5e9' ?>; font-size: 0.85rem;"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="mb-0 text-muted" style="font-size: 0.88rem; line-height: 1.5;"><?= nl2br(htmlspecialchars($review->comment ?? '')) ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        </section>

    </div>

    <!-- ══ RIGHT ══ -->
    <div class="col-lg-5">
        <div class="right-sticky">

            <!-- Booking card -->
            <div class="bk-card" id="reserve" data-window-frame data-window-title="Đặt phòng">
                @include('client.partials.window-controls', ['label' => 'đặt phòng'])
                @include('client.partials.booking-steps', ['currentStep' => 1])
                <div class="bk-header">
                    <span class="booking-title"><i class="bi bi-calendar-check"></i> Đặt phòng</span>
                    <span class="booking-rate"><strong><?= number_format($room['price'], 0, ',', '.') ?> ₫</strong><span>/ đêm</span></span>
                </div>
                <div class="bk-body">

                    <div class="row g-2 mb-2">
                        <!-- Ngày nhận -->
                        <div class="col-6">
                            <label class="f-label" for="bkCheckIn">Ngày Nhận</label>
                            @php($earliestCheckIn = now('Asia/Ho_Chi_Minh')->hour >= 17 ? now('Asia/Ho_Chi_Minh')->addDay()->toDateString() : now('Asia/Ho_Chi_Minh')->toDateString())
                            <input type="date" id="bkCheckIn" class="f-input" aria-describedby="errCheckIn bookingTimeNote"
                                   min="{{ $earliestCheckIn }}" required
                                   value="<?= htmlspecialchars($_GET['check_in'] ?? '') ?>">
                            <div class="date-error" id="errCheckIn"></div>
                            <small class="booking-time-note" id="bookingTimeNote"><i class="bi bi-clock"></i> Nhận phòng từ 12:00 đến 17:00 muộn nhất.</small>
                        </div>
                        <!-- Ngày trả -->
                        <div class="col-6">
                            <label class="f-label" for="bkCheckOut">Ngày Trả</label>
                            <input type="date" id="bkCheckOut" class="f-input" aria-describedby="errCheckOut"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required
                                   value="<?= htmlspecialchars($_GET['check_out'] ?? '') ?>">
                            <div class="date-error" id="errCheckOut"></div>
                        </div>
                        <!-- Người lớn -->
                        <div class="col-4">
                            <label class="f-label" for="bkAdults">Người Lớn</label>
                            <input type="number" id="bkAdults" class="f-input"
                                   min="1" value="<?= $adults ?>">
                        </div>
                        <!-- Trẻ em -->
                        <div class="col-4">
                            <label class="f-label" for="bkChildren">Trẻ Em</label>
                            <input type="number" id="bkChildren" class="f-input"
                                   min="0" value="<?= $children ?>">
                        </div>
                        <!-- Số đêm -->
                        <div class="col-4">
                            <label class="f-label" for="bkNights">Số Đêm</label>
                            <input type="text" id="bkNights" class="f-input"
                                   placeholder="-" readonly>
                        </div>
                    </div>

                    <!-- Thông báo không đủ sức chứa -->
                    <div id="insufficientCapacityNotice"
                         style="background:#ffebee;border:1px solid #ffcdd2;border-radius:8px;
                                padding:8px 12px;font-size:.82rem;margin-bottom:10px;color:#c62828;
                                <?= $isInsufficient ? '' : 'display:none;' ?>">
                        <i class="bi bi-exclamation-triangle-fill" style="color:#c62828;"></i>
                        Loại phòng này không đủ sức chứa cho số lượng khách đã chọn.
                    </div>

                    <!-- Thông báo multi-room -->
                    <div id="multiRoomNotice"
                         style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;
                                padding:8px 12px;font-size:.82rem;margin-bottom:10px;
                                <?= $multiRoomMode ? '' : 'display:none;' ?>">
                        <i class="bi bi-people-fill" style="color:#f59e0b;"></i>
                        Với <span id="noticeGuests"><?= $adults + $children ?></span> khách, bạn cần chọn
                        ít nhất <strong id="roomsNeededLabel"><?= $roomsNeeded ?></strong> phòng.
                    </div>

            <!-- Room selector -->
            <div class="rs-card">
                <div class="rs-header">
                    <i class="bi bi-grid-3x3-gap"></i>
                    Chọn phòng cụ thể
                    <?php if ($multiRoomMode): ?>
                    <span style="font-size:.75rem;opacity:.75;margin-left:6px;">
                        (chọn <?= $roomsNeeded ?> phòng)
                    </span>
                    <?php endif; ?>
                </div>
                <div class="rs-body">

                    <div class="rs-legend">
                        <div class="rs-legend-item">
                            <div class="rs-dot avail"></div><span>Còn trống</span>
                        </div>
                        <div class="rs-legend-item">
                            <div class="rs-dot sel"></div><span>Đang chọn</span>
                        </div>
                        <div class="rs-legend-item">
                            <div class="rs-dot taken"></div><span>Đã đặt</span>
                        </div>
                    </div>

                    <?php
                    $datesSelected = !empty($checkIn) && !empty($checkOut);
                    $byFloorDisplay = [];
                    foreach ($byFloor as $floor => $floorRooms) {
                        $byFloorDisplay[$floor] = $floorRooms;
                    }
                    ?>
                    <?php if (empty($byFloorDisplay)): ?>
                        <p class="rs-empty">Chưa có phòng nào cho loại này.</p>
                    <?php else: ?>
                        <?php foreach ($byFloorDisplay as $floor => $floorRooms): ?>
                        <div class="floor-lbl">Tầng <?= $floor ?></div>
                        <div class="floor-rooms">
                            <?php foreach ($floorRooms as $r):
                                $taken = ($bookedRoomIds ?? collect())->contains($r['id']);
                            ?>
                            <button type="button"
                                    class="rm-btn <?= ($taken || $isInsufficient) ? 'taken' : '' ?>"
                                    data-room-id="<?= $r['id'] ?>"
                                    data-room-number="<?= htmlspecialchars($r['room_number']) ?>"
                                    data-is-booked="<?= $taken ? 'true' : 'false' ?>"
                                    <?= ($taken || $isInsufficient) ? 'disabled title="' . ($isInsufficient ? 'Loại phòng không đủ sức chứa' : 'Phòng đã được đặt') . '"' : 'onclick="toggleRoom(this)"' ?>>
                                <?= htmlspecialchars($r['room_number']) ?>
                                <span class="rm-sub"><?= $taken ? 'Đã đặt' : ($isInsufficient ? 'Không đủ chỗ' : 'Trống') ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>

                    <div class="booking-estimate booking-estimate--final" aria-live="polite">
                        <div class="booking-estimate__row"><span>Số đêm</span><strong id="estimateNights">Chưa chọn</strong></div>
                        <div class="booking-estimate__row"><span>Số phòng</span><strong id="estimateRooms"><?= $roomsNeeded < 999 ? $roomsNeeded : 1 ?></strong></div>
                        <div class="booking-estimate__row booking-estimate__total"><span>Tạm tính</span><strong id="estimateTotal">0 ₫</strong></div>
                    </div>

                    <?php if (empty(session('user_id'))): ?>
                        <!-- Chưa đăng nhập -->
                        <button type="button" class="btn-book" id="btnBook" onclick="submitBooking()">
                            <i class="bi bi-lock me-1"></i>Đăng nhập để đặt phòng
                        </button>
                        <p class="bk-hint">
                            Vui lòng chọn ngày và phòng để tiếp tục
                        </p>
                    <?php else: ?>
                        <!-- Đã đăng nhập -->
                        <button type="button" class="btn-book" id="btnBook"
                                disabled onclick="submitBooking()">
                            <i class="bi bi-calendar-plus me-1"></i>Đặt Ngay
                        </button>
                        <p class="bk-hint" id="bkHint">
                            <?= $isInsufficient
                                ? 'Loại phòng này không đủ sức chứa cho số lượng khách đã chọn.'
                                : ($multiRoomMode
                                    ? "Chọn đủ $roomsNeeded phòng phía trên"
                                    : 'Vui lòng chọn một phòng phía trên') ?>
                        </p>
                    <?php endif; ?>

                </div>
            </div>

        </div><!-- /sticky -->
    </div><!-- /right col -->

</div><!-- /row -->

<!-- Booking bar dính dưới -->
<?php if (!empty(session('user_id'))): ?>
<div class="bk-bar" id="bkBar">
    <div>
        <div style="font-weight:700;font-size:.95rem;margin-bottom:4px;">
            Đã chọn <span id="bkBarCount">0</span> phòng
            <span id="bkBarMin" style="font-size:.8rem;opacity:.75;">
                (tối thiểu <span id="bkBarNeeded"><?= $roomsNeeded ?></span>)
            </span>
            <span id="bkBarTotal" style="color:var(--gold);margin-left:10px;"></span>
        </div>
        <div class="bk-bar-labels" id="bkBarLabels"></div>
        <div class="bk-capacity" id="bkBarCapacity"></div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <button class="btn-bk-clear" onclick="clearRooms()">
            <i class="bi bi-x-circle me-1"></i>Bỏ chọn
        </button>
        <button class="btn-bk-submit" id="btnBarSubmit" disabled onclick="submitBooking()">
            <i class="bi bi-calendar-check me-1"></i>Đặt Phòng Đã Chọn
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Hidden form để submit -->
<form method="GET" action="{{ route('booking.create') }}" id="bookingForm" style="display:none;">
    <input type="hidden" name="check_in"   id="formCheckIn"  value="">
    <input type="hidden" name="check_out"  id="formCheckOut" value="">
    <input type="hidden" name="adults"     id="formAdults"   value="<?= $adults ?>">
    <input type="hidden" name="children"   id="formChildren" value="<?= $children ?>">
    <div id="formRoomIds"></div>
</form>
<div class="capacity-notice" id="capacityNotice" role="alert" aria-live="assertive"></div>

<!-- Modal cảnh báo sức chứa -->
<div class="modal fade" id="capacityModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Chưa đủ sức chứa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="capacityModalMsg"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Quay lại chọn phòng</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════ -->
<script>
// ═══════════════════════════════════════════════
// 1. KHAI BÁO & BIẾN TOÀN CỤC
// ═══════════════════════════════════════════════
const IS_LOGGED_IN = <?= empty(session('user_id')) ? 'false' : 'true' ?>;
const TODAY        = '{{ $earliestCheckIn }}';
const inEl         = document.getElementById('bkCheckIn');
const outEl        = document.getElementById('bkCheckOut');
const adultsInput  = document.getElementById('bkAdults');
const childrenInput= document.getElementById('bkChildren');

function civilDate(value) {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(Date.UTC(year, month - 1, day));
}
function civilDayNumber(value) {
    return civilDate(value).getTime() / 86400000;
}

let   ROOMS_NEEDED = <?= $roomsNeeded ?>;
const MAX_ADULTS   = <?= $maxAdults ?>;
const MAX_CHILDREN = <?= $maxChildren ?>;
const MAX_GUESTS   = <?= $maxGuests ?>;
let PRICE          = <?= (float)$room['price'] ?>;

let availabilityPending = false;
let availabilityRequest;
const selectedRooms = new Map(); // roomId → roomNumber

document.querySelectorAll('[data-room-gallery-image]').forEach((button) => {
    button.addEventListener('click', () => {
        const primary = document.getElementById('roomGalleryPrimary');
        const thumbImage = button.querySelector('img');
        if (!primary || !thumbImage) return;

        const nextSource = button.dataset.roomGalleryImage;
        const currentSource = primary.getAttribute('src');
        const currentAlt = primary.getAttribute('alt');

        const swap = () => {
            primary.setAttribute('src', nextSource);
            primary.setAttribute('alt', thumbImage.getAttribute('alt') || currentAlt);
            thumbImage.setAttribute('src', currentSource);
            button.dataset.roomGalleryImage = currentSource;
        };

        if (window.gsap && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            window.gsap.to(primary, { opacity: 0, scale: 1.015, duration: .18, ease: 'power2.out', onComplete: () => {
                swap();
                window.gsap.fromTo(primary, { opacity: 0, scale: .99 }, { opacity: 1, scale: 1, duration: .42, ease: 'power3.out' });
            }});
        } else {
            swap();
        }
    });
});

// ═══════════════════════════════════════════════
// 2. XỬ LÝ NGÀY THÁNG & SỐ ĐÊM
// ═══════════════════════════════════════════════
function markInvalid(el) { el.classList.remove('is-valid');   el.classList.add('is-invalid'); }
function markValid(el)   { el.classList.remove('is-invalid'); el.classList.add('is-valid');   }
function showErr(id, msg) {
    const el = document.getElementById(id);
    if (el) el.innerHTML = `<i class="bi bi-exclamation-circle-fill"></i> ${msg}`;
}
function hideErr(id) {
    const el = document.getElementById(id);
    if (el) el.innerHTML = '';
}

function calcNightsNum() {
    if (inEl && outEl && inEl.value && outEl.value && outEl.value > inEl.value) {
        return civilDayNumber(outEl.value) - civilDayNumber(inEl.value);
    }
    return 0;
}

function updateEstimate() {
    const nights = calcNightsNum();
    const roomCount = selectedRooms.size || (ROOMS_NEEDED > 0 && ROOMS_NEEDED < 999 ? ROOMS_NEEDED : 1);
    const nightsEl = document.getElementById('estimateNights');
    const roomsEl = document.getElementById('estimateRooms');
    const totalEl = document.getElementById('estimateTotal');
    if (nightsEl) nightsEl.textContent = nights > 0 ? `${nights} đêm` : 'Chưa chọn';
    if (roomsEl) roomsEl.textContent = roomCount;
    if (totalEl) totalEl.textContent = `${(nights * roomCount * PRICE).toLocaleString('vi-VN')} ₫`;
}

function calcNights() {
    const ni = document.getElementById('bkNights');
    const n  = calcNightsNum();
    if (ni) ni.value = n > 0 ? n + ' đêm' : '-';
    updateEstimate();
    updateBookBtn();
}

function syncOutMin() {
    if (!inEl || !inEl.value) return;
    const next = civilDate(inEl.value);
    next.setUTCDate(next.getUTCDate() + 1);
    if (outEl) {
        outEl.min = next.toISOString().slice(0, 10);
        if (outEl.value && outEl.value <= inEl.value) {
            outEl.value = '';
            markInvalid(outEl);
            showErr('errCheckOut', 'Ngày trả phòng phải sau ngày nhận phòng.');
        }
    }
    calcNights();
}

if (inEl) {
    inEl.addEventListener('change', function () {
        if (!inEl.value) {
            markInvalid(inEl); showErr('errCheckIn', 'Vui lòng chọn ngày nhận phòng.'); return;
        }
        if (inEl.value < TODAY) {
            markInvalid(inEl); showErr('errCheckIn', 'Không thể chọn ngày trong quá khứ.'); return;
        }
        markValid(inEl); hideErr('errCheckIn');
        syncOutMin();
        refreshAvailability();
    });
}

if (outEl) {
    outEl.addEventListener('change', function () {
        if (!outEl.value) {
            markInvalid(outEl); showErr('errCheckOut', 'Vui lòng chọn ngày trả phòng.'); return;
        }
        if (outEl.value <= inEl.value) {
            outEl.value = '';
            markInvalid(outEl); showErr('errCheckOut', 'Ngày trả phòng phải sau ngày nhận phòng.'); return;
        }
        markValid(outEl); hideErr('errCheckOut');
        calcNights();

        refreshAvailability();
    });
}

async function refreshAvailability() {
    availabilityRequest?.abort();
    if (!inEl.value || !outEl.value || inEl.value < TODAY || outEl.value <= inEl.value) {
        availabilityPending = true;
        updateBookBtn();
        return;
    }
    const controller = new AbortController();
    availabilityRequest = controller;
    availabilityPending = true;
    updateBookBtn();
    const url = new URL(location.href);
    url.searchParams.set('check_in', inEl.value);
    url.searchParams.set('check_out', outEl.value);
    url.searchParams.set('adults', adultsInput.value);
    url.searchParams.set('children', childrenInput.value);
    try {
        const response = await fetch(url, {cache:'no-store', signal:controller.signal, headers:{Accept:'application/json'}});
        if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Không thể cập nhật phòng trống. Vui lòng chọn lại ngày.');
        const data = await response.json();
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || 'Không thể cập nhật phòng trống. Vui lòng thử lại.');
        PRICE = data.nightly_price;
        const available = new Set(data.available_ids.map(String));
        document.querySelectorAll('.rm-btn').forEach(button => {
            const booked = !available.has(button.dataset.roomId);
            button.dataset.isBooked = String(booked);
            button.disabled = booked;
            button.classList.toggle('taken', booked);
            if (booked) {
                selectedRooms.delete(button.dataset.roomId);
                button.classList.remove('selected');
            }
            button.setAttribute('onclick', 'toggleRoom(this)');
            button.querySelector('.rm-sub').textContent = booked ? 'Không còn trống' : (selectedRooms.has(button.dataset.roomId) ? 'Đang chọn' : 'Trống');
        });
        hideErr('errCheckOut');
        const rate = document.querySelector('.booking-rate strong');
        if (rate) rate.textContent = new Intl.NumberFormat('vi-VN').format(PRICE) + ' ₫';
        history.replaceState(null, '', url);
        availabilityPending = false;
        updateEstimate();
    } catch (error) {
        if (error.name !== 'AbortError') {
            document.getElementById('errCheckOut').textContent = error.message;
            document.getElementById('errCheckOut').style.display = 'block';
        }
    } finally {
        if (availabilityRequest === controller) updateBookBtn();
    }
}
window.addEventListener('pageshow', () => {
    if (inEl.value && outEl.value) refreshAvailability();
});

// ═══════════════════════════════════════════════
// 3. CHỌN PHÒNG & VALIDATE
// ═══════════════════════════════════════════════
window.toggleRoom = function(btn) {
    const id  = btn.dataset.roomId;
    const num = btn.dataset.roomNumber;

    if (btn.classList.contains('selected')) {
        btn.classList.remove('selected');
        const sub = btn.querySelector('.rm-sub');
        if (sub) sub.textContent = 'Trống';
        selectedRooms.delete(id);
    } else {
        btn.classList.add('selected');
        const sub = btn.querySelector('.rm-sub');
        if (sub) sub.textContent = 'Đã chọn';
        selectedRooms.set(id, num);
    }
    updateBookBtn();
    updateEstimate();
    if (typeof updateBar === 'function') updateBar();
};

window.clearRooms = function() {
    selectedRooms.clear();
    document.querySelectorAll('.rm-btn.selected').forEach(b => {
        b.classList.remove('selected');
        const sub = b.querySelector('.rm-sub');
        if (sub) sub.textContent = 'Trống';
    });
    updateBookBtn();
    updateEstimate();
    if (typeof updateBar === 'function') updateBar();
};

function updateBookBtn() {
    const ready = !availabilityPending && inEl.value >= TODAY && selectedRooms.size >= ROOMS_NEEDED
                  && inEl.value && outEl.value
                  && outEl.value > inEl.value;
    const btn = document.getElementById('btnBook');
    if (btn) btn.disabled = !ready;
    const barBtn = document.getElementById('btnBarSubmit');
    if (barBtn) barBtn.disabled = !ready;
}

function recalculateRoomsNeeded() {
    const adults = Math.max(1, parseInt(adultsInput.value) || 1);
    const children = Math.max(0, parseInt(childrenInput.value) || 0);
    const totalGuests = adults + children;

    const isInsufficient = (MAX_ADULTS === 0 && adults > 0) || (MAX_CHILDREN === 0 && children > 0) || MAX_GUESTS === 0;
    
    const insNotice = document.getElementById('insufficientCapacityNotice');
    if (insNotice) {
        insNotice.style.display = isInsufficient ? '' : 'none';
    }

    document.querySelectorAll('.rm-btn').forEach(btn => {
        const isBooked = btn.dataset.isBooked === 'true';
        if (isInsufficient) {
            btn.classList.add('taken');
            btn.disabled = true;
            btn.title = 'Loại phòng không đủ sức chứa';
            const sub = btn.querySelector('.rm-sub');
            if (sub) sub.textContent = 'Không đủ chỗ';
        } else {
            if (isBooked) {
                btn.classList.add('taken');
                btn.disabled = true;
                btn.title = 'Phòng đã được đặt';
                const sub = btn.querySelector('.rm-sub');
                if (sub) sub.textContent = 'Đã đặt';
            } else {
                const isSelected = btn.classList.contains('selected');
                btn.classList.remove('taken');
                btn.disabled = false;
                btn.title = '';
                const sub = btn.querySelector('.rm-sub');
                if (sub) sub.textContent = isSelected ? 'Đang chọn' : 'Trống';
            }
        }
    });

    if (isInsufficient) {
        ROOMS_NEEDED = 999;
    } else {
        ROOMS_NEEDED = 1;
        while (true) {
            const totalMaxAdults = ROOMS_NEEDED * MAX_ADULTS;
            const totalMaxChildren = ROOMS_NEEDED * MAX_CHILDREN;
            const totalMaxGuests = ROOMS_NEEDED * MAX_GUESTS;
            if (totalMaxAdults >= adults && totalMaxChildren >= children && totalMaxGuests >= totalGuests) {
                break;
            }
            ROOMS_NEEDED++;
            if (ROOMS_NEEDED > 100) {
                ROOMS_NEEDED = 999;
                break;
            }
        }
    }

    const barNeeded = document.getElementById('bkBarNeeded');
    if (barNeeded) barNeeded.textContent = ROOMS_NEEDED;

    const lbl = document.getElementById('roomsNeededLabel');
    if (lbl) lbl.textContent = ROOMS_NEEDED;

    const noticeGuests = document.getElementById('noticeGuests');
    if (noticeGuests) noticeGuests.textContent = totalGuests;

    const notice = document.getElementById('multiRoomNotice');
    if (notice) notice.style.display = (ROOMS_NEEDED > 1 && ROOMS_NEEDED < 999) ? '' : 'none';

    const hint = document.getElementById('bkHint');
    if (hint) {
        if (isInsufficient) {
            hint.textContent = 'Loại phòng này không đủ sức chứa cho số lượng khách đã chọn.';
        } else {
            hint.textContent = ROOMS_NEEDED === 1
                ? 'Vui lòng chọn một phòng phía trên'
                : `Chọn đủ ${ROOMS_NEEDED} phòng phía trên`;
        }
    }
    clearRooms();
}

if (adultsInput)   adultsInput.addEventListener('input', recalculateRoomsNeeded);
if (childrenInput) childrenInput.addEventListener('input', recalculateRoomsNeeded);

// ═══════════════════════════════════════════════
// 4. SUBMIT ĐẶT PHÒNG
// ═══════════════════════════════════════════════
window.submitBooking = function() {
    if (availabilityPending || inEl.value < TODAY) { showErr('errCheckIn', 'Vui lòng chọn ngày hợp lệ và chờ cập nhật phòng trống.'); return; }
    if (!inEl.value) {
        markInvalid(inEl); showErr('errCheckIn', 'Vui lòng chọn ngày nhận phòng.'); return;
    }
    if (!outEl.value || outEl.value <= inEl.value) {
        markInvalid(outEl); showErr('errCheckOut', 'Ngày trả phòng phải sau ngày nhận phòng.'); return;
    }
    if (selectedRooms.size === 0) {
        const notice = document.getElementById('capacityNotice'); notice.textContent = 'Vui lòng chọn ít nhất một phòng.'; notice.classList.add('is-visible'); return;
    }

    const totalMaxAdults = selectedRooms.size * MAX_ADULTS;
    const totalMaxChildren = selectedRooms.size * MAX_CHILDREN;
    const totalMaxGuests = selectedRooms.size * MAX_GUESTS;
    
    const neededAdults = parseInt(adultsInput.value) || 1;
    const neededChildren = parseInt(childrenInput.value) || 0;
    const neededGuests = neededAdults + neededChildren;

    if (totalMaxAdults < neededAdults || totalMaxChildren < neededChildren || totalMaxGuests < neededGuests) {
        const notice = document.getElementById('capacityNotice');
        if (notice) {
            notice.textContent = `Phòng đã chọn chỉ phù hợp tối đa ${totalMaxGuests} khách. Vui lòng chọn thêm phòng hoặc giảm số khách.`;
            notice.classList.add('is-visible');
            window.gsap?.fromTo(notice,{y:-8,opacity:0},{y:0,opacity:1,duration:.35,ease:'power2.out'});
        }
        return;
    }

    document.getElementById('formCheckIn').value  = inEl.value;
    document.getElementById('formCheckOut').value = outEl.value;
    document.getElementById('formAdults').value   = adultsInput.value;
    document.getElementById('formChildren').value = childrenInput.value;

    const formRoomIds = document.getElementById('formRoomIds');
    formRoomIds.innerHTML = '';
    selectedRooms.forEach((num, id) => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'room_ids[]'; inp.value = id;
        formRoomIds.appendChild(inp);
    });

    document.getElementById('bookingForm').submit();
};

// ═══════════════════════════════════════════════
// 5. KHỞI TẠO BAN ĐẦU & LOGGED IN ONLY
// ═══════════════════════════════════════════════
if (inEl && inEl.value) syncOutMin();
if (inEl && outEl && inEl.value && outEl.value) calcNights();
updateBookBtn();
updateEstimate();

if (IS_LOGGED_IN) {
    window.updateBar = function() {
        const count  = selectedRooms.size;
        const bar    = document.getElementById('bkBar');
        if (!bar) return;

        if (count === 0) {
            bar.classList.remove('visible');
            document.body.style.paddingBottom = '0';
            return;
        }

        bar.classList.remove('visible');
        document.body.style.paddingBottom = '0';

        const nights   = calcNightsNum();
        const total    = count * nights * PRICE;
        const totalMaxAdults = count * MAX_ADULTS;
        const totalMaxChildren = count * MAX_CHILDREN;
        const totalMaxGuests = count * MAX_GUESTS;

        document.getElementById('bkBarCount').textContent = count;

        const labelsEl = document.getElementById('bkBarLabels');
        if (labelsEl) {
            labelsEl.innerHTML = '';
            selectedRooms.forEach(num => {
                const span = document.createElement('span');
                span.className   = 'bk-label-badge';
                span.textContent = 'Phòng ' + num;
                labelsEl.appendChild(span);
            });
        }

        const totalEl = document.getElementById('bkBarTotal');
        if (totalEl) {
            totalEl.textContent = nights > 0 ? total.toLocaleString('vi-VN') + ' VNĐ (' + nights + ' đêm)' : '';
        }

        const capEl  = document.getElementById('bkBarCapacity');
        const neededAdults = parseInt(adultsInput.value) || 1;
        const neededChildren = parseInt(childrenInput.value) || 0;
        const neededGuests = neededAdults + neededChildren;
        if (capEl) {
            capEl.className = 'bk-capacity ok';
            capEl.innerHTML = '';
        }
    };
}
</script>
@endsection
