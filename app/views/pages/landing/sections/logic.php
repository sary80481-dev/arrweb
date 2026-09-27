<?php
// app/views/pages/landing/sections/logic.php — Game Logic: poin + jendela kode yang "diketik"
?>
<section class="lx-logic">
  <div class="lx-logic-text">
    <p class="lx-eyebrow lx-reveal">Game Logic</p>
    <h2 class="lx-h2 lx-reveal">GUI kamu sudah<br>tahu cara bekerja.</h2>
    <ul class="lx-checks">
      <li class="lx-reveal"><b>Harga dari desain</b> — "💎 2,500" di card jadi katalog item.</li>
      <li class="lx-reveal"><b>Saldo dari header</b> — "💎 12,450" jadi leaderstats awal pemain.</li>
      <li class="lx-reveal"><b>Anti-exploit</b> — client cuma kirim id, harga dicek di server.</li>
      <li class="lx-reveal"><b>Tersimpan</b> — saldo, inventory, setting pakai DataStore.</li>
    </ul>
  </div>

  <div class="lx-code-window lx-reveal">
    <div class="lx-window-bar"><i></i><i></i><i></i><span>ArrUIServer.server.lua</span></div>
    <pre id="lxTyped" data-code="function Handlers.Purchase(player, itemId)
	if not validItem(itemId) then
		return false, &quot;Item tidak dikenal&quot;
	end
	local item = Config.Items[itemId]
	local ok, err = spend(player, item.Currency, item.Price)
	if not ok then return false, err end
	addItem(player, itemId, 1)
	return true, &quot;Berhasil beli &quot; .. item.DisplayName
end"></pre>
  </div>
</section>
