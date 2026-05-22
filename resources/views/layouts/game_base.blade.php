@extends('layouts.app')

@section('content')
<div id="game-wrapper" class="container-fluid p-0">
    <div id="game-header" class="d-flex align-items-center justify-content-between px-3 py-2">
        <div class="d-flex align-items-center">
            <i class="fa-solid fa-circle-chevron-left me-3 text-muted btn-back" onclick="window.location='{{ route('home') }}'" style="cursor: pointer;"></i>
            <h6 class="m-0 text-white fw-bold text-neon">@yield('game_title', 'GAME')</h6>
        </div>
        <div class="text-muted small fw-bold font-mono text-cyan-glow">@yield('game_version', 'VER 1.0')</div>
    </div>
    
    <div id="game-container"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/phaser@3.60.0/dist/phaser.min.js"></script>
@yield('game_script')

<style>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@700&display=swap');
body { background-color: #020305; margin: 0; padding: 0; overflow: hidden; width: 100vw; height: 100vh; }
#game-wrapper { width: 100%; height: 100vh; display: flex; flex-direction: column; background: #020305; }
#game-header { background: linear-gradient(180deg, rgba(5,6,11,0.95) 0%, rgba(2,3,5,0) 100%); border-bottom: 1px solid rgba(0, 255, 255, 0.08); height: 50px; }
.text-neon { font-family: 'Orbitron', sans-serif; color: #fff; text-shadow: 0 0 10px #00ffff, 0 0 20px rgba(0,255,255,0.3); font-size: 0.95rem; letter-spacing: 1px; }
.text-cyan-glow { color: #00ffff !important; text-shadow: 0 0 8px rgba(0,255,255,0.6); }
#game-container { flex-grow: 1; display: flex; justify-content: center; align-items: center; padding: 5px; }
canvas { max-width: 100%; max-height: calc(100vh - 60px); width: auto !important; height: auto !important; aspect-ratio: 450 / 800; border: 2px solid #141929; border-radius: 16px; box-shadow: 0 25px 60px rgba(0,0,0,0.85); }
.btn-back { font-size: 1.3rem; transition: all 0.2s ease; }
.btn-back:hover { color: #00ffff !important; transform: scale(1.1); }
.container { padding: 0 !important; max-width: none !important; }
.fixed-top, .footer { display: none !important; }
main { padding: 0 !important; }
@yield('game_style')
</style>
@endsection
