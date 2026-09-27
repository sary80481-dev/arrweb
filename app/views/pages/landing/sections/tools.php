<?php
// app/views/pages/landing/sections/tools.php — Creator Tools: YT → MP3, Auto Spoof, Riwayat Upload
// Tools butuh login: guest diarahkan ke halaman login (setelah login balik ke tool-nya)
$loggedIn = Auth::check();
$tools = [
    [
        'id'    => 'ytmp3',
        'name'  => 'YT → MP3',
        'title' => 'Link YouTube jadi audio game.',
        'points' => [
            ['Massal', 'banyak link / playlist sekaligus, download satu ZIP'],
            ['Speed &amp; pitch', 'atur x1.00–x2.00 &amp; ±6 semitone + script kompensasi Luau'],
            ['Langsung ke Roblox', 'upload dari server, nama aset dipendekkan otomatis'],
        ],
        'cta' => 'Buka YT → MP3',
    ],
    [
        'id'    => 'spoofer',
        'name'  => 'Auto Spoof',
        'title' => 'Re-upload aset massal ke akunmu.',
        'points' => [
            ['Tiga sumber', 'asset ID, file, atau daftar <code>Nama{ID}</code>'],
            ['Izin game &amp; publik', 'izinkan semua aset ke game dalam satu klik'],
            ['Cek koneksi', 'validasi API key, scope, &amp; tes download sebelum mulai'],
        ],
        'cta' => 'Buka Auto Spoof',
    ],
    [
        'id'    => 'history',
        'name'  => 'Riwayat Upload',
        'title' => 'Semua upload, satu tempat.',
        'points' => [
            ['Status review', 'dicek otomatis sampai <em>Siap dipakai</em> — ada notifikasi'],
            ['Cari &amp; filter', 'per sumber, status review, &amp; izin game'],
            ['Copy cepat', 'ID, <code>rbxassetid://</code>, atau tabel Lua'],
        ],
        'cta' => 'Lihat Riwayat',
    ],
    [
        'id'    => 'luaobf',
        'name'  => 'Lua Obfuscator',
        'title' => 'Lindungi script, bisa dibalik.',
        'points' => [
            ['Reversibel penuh', 'seribet apa pun, kembali jadi kode Lua asli'],
            ['Tetap jalan', 'output loader yang <code>load()</code> dirinya sendiri di Roblox'],
            ['Privat', 'diproses di browser, kode tak dikirim ke server'],
        ],
        'cta' => 'Buka Lua Obfuscator',
    ],
];
?>
<section class="lx-tools" id="lxTools">
  <div class="lx-section-head lx-section-head--center">
    <p class="lx-eyebrow lx-reveal">Creator Tools · Baru</p>
    <h2 class="lx-h2 lx-reveal">Bukan cuma converter.</h2>
    <p class="lx-lead lx-reveal">
      Audio, aset, sampai status review Roblox — dikerjakan massal dari satu dashboard,
      lewat API key Open Cloud milikmu sendiri.<?= $loggedIn ? '' : ' Gratis — cukup login dengan Google / Discord.' ?>
    </p>
  </div>

  <div class="lx-tools-grid">
    <?php foreach ($tools as $i => $t): ?>
      <article class="lx-tool lx-reveal" style="--d: <?= $i * 0.08 ?>s">
        <div class="lx-tool-mock" aria-hidden="true">
          <?php if ($t['id'] === 'ytmp3'): ?>
            <div class="lx-mock-row"><i class="yt">▶</i><b>Lagu Satu</b><span class="ok">1843…21 ✓</span></div>
            <div class="lx-mock-row"><i class="yt">▶</i><b>Lagu Dua</b><span class="run">upload…</span></div>
            <div class="lx-mock-row"><i class="yt">▶</i><b>Lagu Tiga</b><span>antri</span></div>
            <div class="lx-mock-fx"><span>Speed <b>x1.25</b></span><span>Pitch <b>+2</b></span></div>
          <?php elseif ($t['id'] === 'spoofer'): ?>
            <div class="lx-mock-map"><span>95440196</span><i>→</i><b>18430012</b></div>
            <div class="lx-mock-map"><span>logo.png</span><i>→</i><b>18430013</b></div>
            <div class="lx-mock-map"><span>Intro{7722}</span><i>→</i><b>18430014</b></div>
            <div class="lx-mock-pill">✓ 3 aset diizinkan ke game</div>
          <?php elseif ($t['id'] === 'history'): ?>
            <div class="lx-mock-row"><b>Lagu Satu</b><span class="ok">Siap dipakai</span></div>
            <div class="lx-mock-row"><b>logo.png</b><span class="ok">🌐 publik</span></div>
            <div class="lx-mock-row"><b>Lagu Dua</b><span class="run">Direview…</span></div>
            <div class="lx-mock-pill">⧉ Copy 3 ID</div>
          <?php else: ?>
            <div class="lx-mock-code">local <b>a</b>="\104\105"<br>print(<b>a</b>)</div>
            <div class="lx-mock-arrow">obfuscate ↓ &nbsp; deobfuscate ↑</div>
            <div class="lx-mock-code lx-mock-code--obf">_ARRR="Zk9y…Q=="<br>load(_d(_ARRR))()</div>
          <?php endif; ?>
        </div>

        <div class="lx-tool-body">
          <span class="lx-tool-num"><?= sprintf('%02d', $i + 1) ?> · <?= e($t['name']) ?></span>
          <h3><?= e($t['title']) ?></h3>
          <ul>
            <?php foreach ($t['points'] as [$b, $desc]): ?>
              <li><b><?= $b ?></b> — <?= $desc ?></li>
            <?php endforeach; ?>
          </ul>
          <?php if ($loggedIn): ?>
            <a href="<?= url($t['id']) ?>" class="lx-link"><?= e($t['cta']) ?> <span>→</span></a>
          <?php else: ?>
            <a href="<?= url($t['id']) ?>" class="lx-link" data-no-spa>Masuk untuk memakai <span>→</span></a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
