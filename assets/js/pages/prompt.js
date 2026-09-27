/* ============================================================
 *  assets/js/pages/prompt.js — JS khusus halaman prompt (SPA-ready)
 * ============================================================ */
(function () {
  'use strict';

  function initPrompt() {
    // Guard: pastikan di halaman prompt
    if (!document.getElementById('pgTabs')) return;

    // Skip kalau udah pernah init
    if (window.__promptInitialized) return;
    window.__promptInitialized = true;

    /* ============================================================
       TABS — event delegation
       ============================================================ */
    const tabsContainer = document.getElementById('pgTabs');
    if (tabsContainer) {
      tabsContainer.addEventListener('click', (e) => {
        const tab = e.target.closest('.pg-tab');
        if (!tab) return;
        const target = tab.dataset.tab;
        if (!target) return;

        document.querySelectorAll('.pg-tab').forEach(t => {
          t.classList.toggle('active', t === tab);
        });
        document.querySelectorAll('.pg-panel').forEach(p => {
          p.classList.toggle('active', p.dataset.panel === target);
        });
      });
    }

    /* ============================================================
       TEMPLATE ACCORDION
       ============================================================ */
    document.querySelectorAll('.pg-template-head').forEach(head => {
      head.addEventListener('click', (e) => {
        if (e.target.closest('.pg-copy')) return;
        head.parentElement.classList.toggle('open');
      });
    });

    /* ============================================================
       CHECKBOX TOGGLE
       ============================================================ */
    document.querySelectorAll('.pg-checkbox').forEach(cb => {
      cb.addEventListener('click', (e) => {
        e.preventDefault();
        cb.classList.toggle('checked');
      });
    });

    /* ============================================================
       COPY HELPERS
       ============================================================ */
    function copyToClipboard(text, btn) {
      if (!navigator.clipboard) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
          document.execCommand('copy');
          showCopied(btn);
        } catch (e) {
          console.error('Fallback copy failed:', e);
        }
        document.body.removeChild(ta);
        return;
      }
      navigator.clipboard.writeText(text).then(() => {
        showCopied(btn);
      }).catch(err => console.error('Copy failed:', err));
    }

    function showCopied(btn) {
      const original = btn.textContent;
      btn.textContent = 'Copied';
      btn.classList.add('copied');
      setTimeout(() => {
        btn.textContent = original;
        btn.classList.remove('copied');
      }, 1500);
    }

    document.querySelectorAll('[data-copy-target]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const target = document.getElementById(btn.dataset.copyTarget);
        if (target) copyToClipboard(target.textContent, btn);
      });
    });

    const genCopyBtn = document.getElementById('genCopy');
    if (genCopyBtn) {
      genCopyBtn.addEventListener('click', () => {
        const out = document.getElementById('genOutput');
        if (out && out.textContent.trim()) {
          copyToClipboard(out.textContent, genCopyBtn);
        }
      });
    }

    document.querySelectorAll('.pg-cheat-tag').forEach(tag => {
      tag.addEventListener('click', () => {
        copyToClipboard(tag.textContent, tag);
      });
    });

    /* ============================================================
       GENERATOR
       ============================================================ */
    function getCheckedFeatures() {
      return Array.from(document.querySelectorAll('.pg-checkbox.checked'))
        .map(cb => cb.dataset.feature);
    }

    function generatePrompt() {
      const uiType     = document.getElementById('genUiType').value;
      const theme      = document.getElementById('genTheme').value;
      const desc       = document.getElementById('genDesc').value.trim() || '[deskripsi UI]';
      const components = document.getElementById('genComponents').value.trim();
      const features   = getCheckedFeatures();
      const notes      = document.getElementById('genNotes').value.trim();

      // BILLBOARD MODE
      if (uiType === 'billboard overhead nametag') {
        let prompt = `Buatkan script Lua untuk BillboardGui overhead nametag di atas kepala player Roblox.

KONTEKS:
Script ini akan dipasang di LocalScript (StarterPlayerScripts) dan bikin BillboardGui otomatis di atas kepala setiap player.
${desc !== '[deskripsi UI]' ? '\nDeskripsi tambahan:\n' + desc + '\n' : ''}
ATURAN WAJIB:
- Pakai BillboardGui, BUKAN ScreenGui
- Nempel di Head (bukan HumanoidRootPart)
- StudsOffsetWorldSpace untuk tinggi di atas kepala (default 3.5)
- AlwaysOnTop = true
- MaxDistance = 120
- LightInfluence = 0
- Apply ke semua player (Players:GetPlayers + PlayerAdded)
- Skip local player
- Guard jika Head belum ke-load (WaitForChild dengan timeout)

STRUKTUR BILLBOARD:
- Ukuran: 220 × 70 px
- Layout dari atas ke bawah:
  1. Row badge icons (3 badge 22×22 px, horizontal, gap 4px):
     - 🎤 background rgba(30,30,40,0.8)
     - 💬 background rgba(30,50,90,0.8)
     - 👑 background rgba(90,30,60,0.8)
     - Border tipis rgba(200,200,210,0.5), border-radius 6px
  2. Nama player: 16px bold, gold #f4d03f, text-stroke hitam
  3. Role text: 12px regular, silver #c0c0c8, text-stroke hitam
  4. Level text: 11px bold, hijau #22c55e, format "Level X"

SERVICES:
- Players
- TweenService (opsional)
`;

        if (components) {
          prompt += `\nKOMPONEN TAMBAHAN:\n`;
          components.split('\n').filter(l => l.trim()).forEach((line, i) => {
            prompt += `${i + 1}. ${line.trim()}\n`;
          });
        }

        if (features.length > 0) {
          prompt += `\nFITUR TAMBAHAN:\n`;
          features.forEach(f => { prompt += `- ${f}\n`; });
        }

        prompt += `\nFITUR WAJIB:
- Auto-update level dari leaderstats.Level kalau ada
- Handle CharacterAdded (respawn)
- Cleanup BillboardGui lama sebelum bikin baru

OUTPUT:
- Full Lua script siap paste ke LocalScript di StarterPlayerScripts
- Modern API (task.wait, :GetService())
- Komentar di tiap fungsi penting
- Jangan jelasin panjang, langsung kode`;

        if (notes) {
          prompt += `\n\nCATATAN TAMBAHAN:\n${notes}`;
        }

        document.getElementById('genOutput').textContent = prompt;
        return;
      }

      // HTML MODE
      let prompt = `Buatkan HTML untuk ${uiType} Roblox yang akan di-convert pakai ARRR Studio Converter.

═══════════════════════════════════════════
ATURAN CONVERTER ARRR STUDIO v5 (WAJIB DIPATUHI)
═══════════════════════════════════════════
- Boleh pakai <style> + class, display:flex, display:grid, margin, padding (converter membaca hasil render browser)
- Satu elemen root pembungkus UI, ukuran muat di 800×600 px
- Warna solid atau linear-gradient; border & border-radius boleh (termasuk per sisi / sebagian)
- transform: rotate() boleh; JANGAN skew
- JANGAN pakai box-shadow, text-shadow, filter, backdrop-filter, radial-gradient, ::before / ::after
- Icon pakai emoji (JANGAN image URL — Roblox butuh rbxassetid)
- Font: Inter / Poppins / Montserrat / Roboto / Arial, size dalam px
- Tombol pakai <button> dengan teks jelas: Buy, Sell, Equip, Claim, Upgrade, Redeem, Spin, Craft, Accept, Decline, Save
- Harga ditulis sebagai teks + ikon mata uang ("💎 2,500", "🪙 900", "$0.99"); saldo pemain di header ("💎 12,450")
- Card item = satu container: ikon, nama, rarity (Common/Rare/Epic/Legendary), harga
- Panel tersembunyi: display:none + id "xxx-panel", tombol buka id "xxx-toggle", tombol tutup id "xxx-close"

═══════════════════════════════════════════
DESKRIPSI UI
═══════════════════════════════════════════
${desc}

═══════════════════════════════════════════
TEMA WARNA
═══════════════════════════════════════════
${theme}

Palet standard ARRR Studio:
- Background: #0a0a0d
- Panel: #121216
- Head: #17171d
- Border: #26262e
- Gold accent: #d4af37 / #f4d03f
- Silver: #c0c0c8
- Text: #e8e8ec
- Text dim: #8a8a96

═══════════════════════════════════════════
KOMPONEN
═══════════════════════════════════════════
`;

      if (components) {
        components.split('\n').filter(l => l.trim()).forEach((line, i) => {
          prompt += `${i + 1}. ${line.trim()}\n`;
        });
      } else {
        prompt += `[list komponen di sini]\n`;
      }

      if (features.length > 0) {
        prompt += `\n═══════════════════════════════════════════
FITUR INTERAKTIF
═══════════════════════════════════════════
`;
        features.forEach(f => {
          prompt += `- ${f}\n`;
        });

        if (features.some(f => f.includes('toggle') || f.includes('close'))) {
          prompt += `\nContoh toggle pattern:
<button data-action="toggle" data-target="myPanel">Open</button>
<div id="myPanel" style="display:none;">...</div>
<button data-action="close" data-target="myPanel">×</button>\n`;
        }
      }

      if (notes) {
        prompt += `\n═══════════════════════════════════════════
CATATAN TAMBAHAN
═══════════════════════════════════════════
${notes}\n`;
      }

      prompt += `\n═══════════════════════════════════════════
OUTPUT
═══════════════════════════════════════════
- Full HTML siap paste ke ARRR Studio Converter input panel
- CSS di dalam satu <style> + class (atau inline style), JANGAN CSS external / framework
- Jangan jelasin panjang, langsung kode
- Setelah HTML, kasih 1 baris catatan: "Paste HTML ini ke ARRR Studio Converter"`;

      document.getElementById('genOutput').textContent = prompt;
    }

    const genBtn = document.getElementById('genBtn');
    if (genBtn) genBtn.addEventListener('click', generatePrompt);

    const genRandom = document.getElementById('genRandom');
    if (genRandom) {
      genRandom.addEventListener('click', () => {
        document.getElementById('genUiType').value = 'HUD in-game';
        document.getElementById('genTheme').value = 'dark + gold accent (#d4af37)';
        document.getElementById('genDesc').value = 'HUD yang nampilin health bar, mana bar, dan skill cooldown di pojok kiri bawah canvas';
        document.getElementById('genComponents').value = `Health bar: 200x20 px, background #ef4444, border-radius 6px, label "HP 100/100" putih di dalam
Mana bar: 200x12 px di bawah health bar (top +26 dari health), background #3b82f6
Skill icons: 3 kotak 48x48 px di kanan health bar, background #17171d, border gold #d4af37 2px, isi emoji ⚔️ 🛡️ 🧪
Text color putih #ffffff, font Arial bold 12px`;
        document.querySelectorAll('.pg-checkbox').forEach(cb => {
          const f = cb.dataset.feature;
          if (f.includes('Hover')) cb.classList.add('checked');
          else cb.classList.remove('checked');
        });
        document.getElementById('genNotes').value = 'Posisi HUD di pojok kiri bawah, saldo koin "🪙 8,420" di pojok kanan atas';
        generatePrompt();
      });
    }

    const genClear = document.getElementById('genClear');
    if (genClear) {
      genClear.addEventListener('click', () => {
        document.getElementById('genUiType').value = 'HUD in-game';
        document.getElementById('genTheme').value = 'dark + gold accent (#d4af37)';
        document.getElementById('genDesc').value = '';
        document.getElementById('genComponents').value = '';
        document.querySelectorAll('.pg-checkbox').forEach(cb => cb.classList.remove('checked'));
        document.getElementById('genNotes').value = '';
        document.getElementById('genOutput').textContent = '';
      });
    }
  }

  /* ============================================================
     EXPOSE
     ============================================================ */
  window.initPrompt = initPrompt;

  /* ============================================================
     AUTO-INIT
     ============================================================ */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPrompt);
  } else {
    initPrompt();
  }

  // Re-init setiap SPA navigation
  window.addEventListener('spa:navigated', () => {
    window.__promptInitialized = false;
    initPrompt();
  });

})();