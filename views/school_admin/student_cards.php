<?php
require dirname(__DIR__) . '/layouts/head.php';
$pdo       = Database::pdo();
$schoolId  = (int)$school['id'];
$className = trim((string)($_GET['class_name'] ?? ''));
$studentId = (int)($_GET['student_id'] ?? 0);

// Fetch distinct classes for quick filter
$clsStmt = $pdo->prepare("SELECT DISTINCT class_name FROM students WHERE school_id = ? AND is_active = 1 ORDER BY class_name ASC");
$clsStmt->execute([$schoolId]);
$allClasses = array_column($clsStmt->fetchAll(), 'class_name');

$sql    = "SELECT * FROM students WHERE school_id = ? AND is_active = 1";
$params = [$schoolId];
if ($studentId > 0) {
    $sql .= " AND id = ?";
    $params[] = $studentId;
} elseif ($className !== '') {
    $sql .= " AND class_name = ?";
    $params[] = $className;
}
$sql .= " ORDER BY class_name ASC, name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$logoUrl    = Tenant::brandingAssetUrl($saas['logo_path'] ?? '', 'assets/img/default-logo.svg');
$faviconUrl = Tenant::brandingAssetUrl($saas['favicon_path'] ?? '', 'assets/img/default-favicon.svg');
$schoolSlug = $school['slug'];
$baseCardsUrl = Tenant::schoolLocalUrl($schoolSlug, 'admin/student-cards');
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<body class="bg-[#F7F9FC] text-slate-800 min-h-screen p-4 sm:p-6 antialiased">
  <!-- Top Action Toolbar -->
  <div class="w-full mb-6 bg-white rounded-2xl p-4 sm:p-5 shadow-[0_2px_12px_rgba(15,23,42,0.04)] border border-slate-200/80 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-2xl bg-[#1E5BF0] text-white flex items-center justify-center text-lg shadow-md shadow-blue-600/20 shrink-0">
        <i class="fa-solid fa-id-card"></i>
      </div>
      <div>
        <h1 class="font-extrabold text-slate-900 text-base sm:text-lg">Kartu Absensi QR Siswa (Portrait Ukuran KTP)</h1>
        <p class="text-xs text-slate-400">
          <?= Security::e($school['name']) ?> • Ukuran standar KTP <strong>54 × 86 mm</strong> (638 × 1012 px @ 300 DPI, Format PNG) • <strong><?= count($students) ?> kartu</strong>
        </p>
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
      <?php if ($studentId === 0): ?>
      <select id="cardClassSelect" onchange="changeCardClass(this.value)" class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 outline-none focus:border-[#1E5BF0]">
        <option value="">Semua Kelas</option>
        <?php foreach ($allClasses as $cls): ?>
          <option value="<?= Security::e($cls) ?>" <?= $className === $cls ? 'selected' : '' ?>>Kelas <?= Security::e($cls) ?></option>
        <?php endforeach; ?>
      </select>
      <?php else: ?>
        <a href="<?= Security::e($baseCardsUrl) ?>" class="px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">
          Lihat Semua Kartu
        </a>
      <?php endif; ?>

      <a href="<?= Tenant::schoolLocalUrl($schoolSlug, 'admin/students') ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">
        <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
      </a>

      <?php if (count($students) > 0): ?>
      <button type="button" id="btnDownloadAll" onclick="downloadAllCards()" class="px-5 py-2.5 rounded-xl bg-[#1E5BF0] hover:bg-[#194ED3] text-white text-xs font-extrabold shadow-md shadow-blue-600/25 transition flex items-center gap-1.5">
        <i class="fa-solid fa-file-zipper"></i>
        <span><?= count($students) === 1 ? 'Download Gambar PNG' : 'Download Semua (' . count($students) . ' PNG / ZIP)' ?></span>
      </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (empty($students)): ?>
    <div class="bg-white rounded-2xl p-12 text-center border border-slate-200 text-slate-400">
      <i class="fa-solid fa-id-card-clip text-4xl mb-3 block text-slate-300"></i>
      <div class="font-bold text-slate-600">Tidak ada data siswa untuk ditampilkan.</div>
    </div>
  <?php else: ?>
    <!-- Portrait KTP Cards Grid -->
    <div id="cardsGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
      <?php foreach ($students as $idx => $st): ?>
        <div class="bg-white rounded-2xl p-3.5 border border-slate-200/80 shadow-[0_2px_12px_rgba(15,23,42,0.04)] flex flex-col items-center gap-3">
          <!-- High-res 638x1012 Canvas (Portrait CR80 KTP ratio 54:86) -->
          <div class="w-full max-w-[250px] aspect-[638/1012] rounded-2xl overflow-hidden shadow-md border border-slate-200 bg-slate-100 relative">
            <canvas id="cardCanvas_<?= $idx ?>" width="638" height="1012" class="w-full h-full block"></canvas>
          </div>

          <div class="w-full flex items-center justify-between gap-2 pt-1">
            <div class="min-w-0">
              <div class="text-xs font-extrabold text-slate-800 truncate"><?= Security::e($st['name']) ?></div>
              <div class="text-[11px] text-slate-400 font-mono truncate"><?= Security::e($st['nisn']) ?> • <?= Security::e($st['class_name']) ?></div>
            </div>
            <button type="button" onclick="downloadSingleCard(<?= $idx ?>)" class="px-3 py-2 rounded-xl bg-[#EEF4FF] hover:bg-[#1E5BF0] text-[#1E5BF0] hover:text-white text-xs font-extrabold transition shrink-0" title="Download Gambar PNG Kartu Ini">
              <i class="fa-solid fa-download mr-1"></i> PNG
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Hidden temp container for crisp QRCode generation -->
  <div id="qrTempHolder" class="hidden"></div>

  <?php require dirname(__DIR__) . '/layouts/modals.php'; ?>

  <script>
    const CARD_STUDENTS = <?= json_encode(array_values($students), JSON_UNESCAPED_UNICODE) ?>;
    const SCHOOL_INFO = {
      name: <?= json_encode($school['name'], JSON_UNESCAPED_UNICODE) ?>,
      slug: <?= json_encode($school['slug'], JSON_UNESCAPED_UNICODE) ?>,
      npsn: <?= json_encode($school['npsn'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
      domain: <?= json_encode(Tenant::schoolDisplayUrl($school['slug']), JSON_UNESCAPED_UNICODE) ?>,
      appName: <?= json_encode($saas['app_name'] ?? 'EduPresensi', JSON_UNESCAPED_UNICODE) ?>,
      logoUrl: <?= json_encode($logoUrl, JSON_UNESCAPED_UNICODE) ?>,
      faviconUrl: <?= json_encode($faviconUrl, JSON_UNESCAPED_UNICODE) ?>
    };
    const FILTER_CLASS = <?= json_encode($className, JSON_UNESCAPED_UNICODE) ?>;

    function changeCardClass(cls) {
      const base = <?= json_encode($baseCardsUrl, JSON_UNESCAPED_UNICODE) ?>;
      window.location.href = cls ? (base + '?class_name=' + encodeURIComponent(cls)) : base;
    }

    function safeFilename(str) {
      return String(str || 'siswa').replace(/[^a-zA-Z0-9_-]+/g, '_').replace(/^_+|_+$/g, '').substring(0, 50);
    }

    function loadImageSafe(src) {
      return new Promise(resolve => {
        if (!src) return resolve(null);
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => resolve(img);
        img.onerror = () => resolve(null);
        img.src = src;
      });
    }

    function generateQrCanvas(text, size) {
      return new Promise(resolve => {
        const holder = document.getElementById('qrTempHolder');
        holder.innerHTML = '';
        const box = document.createElement('div');
        holder.appendChild(box);
        new QRCode(box, {
          text: String(text),
          width: size,
          height: size,
          colorDark: '#0F172A',
          colorLight: '#FFFFFF',
          correctLevel: QRCode.CorrectLevel.H
        });
        setTimeout(() => {
          const c = box.querySelector('canvas');
          if (c) return resolve(c);
          const im = box.querySelector('img');
          if (im && im.complete) return resolve(im);
          if (im) {
            im.onload = () => resolve(im);
            im.onerror = () => resolve(null);
          } else {
            resolve(null);
          }
        }, 30);
      });
    }

    function drawRoundRect(ctx, x, y, w, h, r) {
      ctx.beginPath();
      ctx.moveTo(x + r, y);
      ctx.lineTo(x + w - r, y);
      ctx.quadraticCurveTo(x + w, y, x + w, y + r);
      ctx.lineTo(x + w, y + h - r);
      ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
      ctx.lineTo(x + r, y + h);
      ctx.quadraticCurveTo(x, y + h, x, y + h - r);
      ctx.lineTo(x, y + r);
      ctx.quadraticCurveTo(x, y, x + r, y);
      ctx.closePath();
    }

    function wrapTextCentered(ctx, text, centerX, startY, maxWidth, lineHeight, maxLines) {
      const words = String(text || '').trim().split(/\s+/);
      const lines = [];
      let line = '';
      for (let i = 0; i < words.length; i++) {
        const test = line ? (line + ' ' + words[i]) : words[i];
        if (ctx.measureText(test).width > maxWidth && line) {
          lines.push(line);
          line = words[i];
          if (lines.length === maxLines - 1) {
            const rest = words.slice(i).join(' ');
            lines.push(rest);
            line = '';
            break;
          }
        } else {
          line = test;
        }
      }
      if (line) lines.push(line);
      lines.slice(0, maxLines).forEach((ln, idx) => {
        ctx.fillText(ln, centerX, startY + idx * lineHeight, maxWidth);
      });
      return Math.min(lines.length, maxLines);
    }

    async function renderStudentCard(canvas, student, brandImg) {
      const W = 638;
      const H = 1012;
      const ctx = canvas.getContext('2d');

      // 1. Base white card background
      ctx.fillStyle = '#FFFFFF';
      ctx.fillRect(0, 0, W, H);

      // Subtle light blue decorative bottom tint
      const bgGrad = ctx.createLinearGradient(0, 680, 0, H);
      bgGrad.addColorStop(0, '#FFFFFF');
      bgGrad.addColorStop(1, '#EFF6FF');
      ctx.fillStyle = bgGrad;
      ctx.fillRect(0, 680, W, H - 680);

      // 2. Royal Blue Curved Header
      const hdrGrad = ctx.createLinearGradient(0, 0, W, 270);
      hdrGrad.addColorStop(0, '#1E3A8A');
      hdrGrad.addColorStop(0.55, '#1E5BF0');
      hdrGrad.addColorStop(1, '#2563EB');
      ctx.fillStyle = hdrGrad;
      ctx.beginPath();
      ctx.moveTo(0, 0);
      ctx.lineTo(W, 0);
      ctx.lineTo(W, 240);
      ctx.quadraticCurveTo(W / 2, 295, 0, 240);
      ctx.closePath();
      ctx.fill();

      // Subtle decorative circles in header
      ctx.fillStyle = 'rgba(255,255,255,0.08)';
      ctx.beginPath(); ctx.arc(580, 45, 95, 0, Math.PI * 2); ctx.fill();
      ctx.beginPath(); ctx.arc(55, 200, 70, 0, Math.PI * 2); ctx.fill();

      // 3. Brand Logo Pill at Top Center
      drawRoundRect(ctx, W / 2 - 46, 26, 92, 92, 24);
      ctx.fillStyle = '#FFFFFF';
      ctx.fill();
      if (brandImg) {
        const pad = 14;
        const boxW = 92 - pad * 2;
        const boxH = 92 - pad * 2;
        const ratio = Math.min(boxW / brandImg.width, boxH / brandImg.height);
        const dw = brandImg.width * ratio;
        const dh = brandImg.height * ratio;
        ctx.drawImage(brandImg, W / 2 - dw / 2, 26 + (92 - dh) / 2, dw, dh);
      }

      // 4. School Name & Card Subtitle
      ctx.textAlign = 'center';
      ctx.fillStyle = '#FFFFFF';
      ctx.font = '800 26px "Plus Jakarta Sans", "Segoe UI", Arial, sans-serif';
      const usedLines = wrapTextCentered(ctx, SCHOOL_INFO.name.toUpperCase(), W / 2, 154, W - 70, 31, 2);

      ctx.fillStyle = '#BFDBFE';
      ctx.font = '700 15px "Plus Jakarta Sans", "Segoe UI", Arial, sans-serif';
      ctx.fillText('KARTU ABSENSI PELAJAR', W / 2, 154 + usedLines * 30 + 2);

      // 5. Floating Class Pill Badge
      const classText = 'KELAS ' + String(student.class_name || '-').toUpperCase();
      ctx.font = '800 18px "Plus Jakarta Sans", "Segoe UI", Arial, sans-serif';
      const pillW = Math.max(150, ctx.measureText(classText).width + 48);
      drawRoundRect(ctx, W / 2 - pillW / 2, 244, pillW, 40, 20);
      ctx.fillStyle = '#0F172A';
      ctx.fill();
      ctx.fillStyle = '#FFFFFF';
      ctx.fillText(classText, W / 2, 270);

      // 6. Crisp High-Contrast QR Code Frame
      const qrBoxSize = 368;
      const qrX = (W - qrBoxSize) / 2;
      const qrY = 312;

      ctx.save();
      ctx.shadowColor = 'rgba(30, 91, 240, 0.14)';
      ctx.shadowBlur = 24;
      ctx.shadowOffsetY = 8;
      drawRoundRect(ctx, qrX, qrY, qrBoxSize, qrBoxSize, 28);
      ctx.fillStyle = '#FFFFFF';
      ctx.fill();
      ctx.restore();

      drawRoundRect(ctx, qrX, qrY, qrBoxSize, qrBoxSize, 28);
      ctx.strokeStyle = '#BFDBFE';
      ctx.lineWidth = 3;
      ctx.stroke();

      const qrImg = await generateQrCanvas(student.qr_token, 320);
      if (qrImg) {
        ctx.imageSmoothingEnabled = false;
        ctx.drawImage(qrImg, qrX + 24, qrY + 24, 320, 320);
        ctx.imageSmoothingEnabled = true;
      }

      // 7. Student Name
      ctx.textAlign = 'center';
      ctx.fillStyle = '#0F172A';
      ctx.font = '800 30px "Plus Jakarta Sans", "Segoe UI", Arial, sans-serif';
      const nameLines = wrapTextCentered(ctx, student.name, W / 2, 728, W - 76, 36, 2);

      // 8. Info Box (NISN & Gender)
      const infoY = nameLines > 1 ? 788 : 764;
      drawRoundRect(ctx, 48, infoY, W - 96, 108, 20);
      ctx.fillStyle = '#F1F6FF';
      ctx.fill();
      ctx.strokeStyle = '#DBEAFE';
      ctx.lineWidth = 2;
      ctx.stroke();

      // Divider vertical line
      ctx.beginPath();
      ctx.moveTo(W / 2, infoY + 18);
      ctx.lineTo(W / 2, infoY + 90);
      ctx.strokeStyle = '#CBD5E1';
      ctx.lineWidth = 1.5;
      ctx.stroke();

      // Left column: NISN
      ctx.textAlign = 'center';
      ctx.fillStyle = '#64748B';
      ctx.font = '700 14px "Plus Jakarta Sans", Arial, sans-serif';
      ctx.fillText('NISN / NIS', 48 + (W - 96) * 0.25, infoY + 38);
      ctx.fillStyle = '#1E3A8A';
      ctx.font = '800 22px Consolas, "Courier New", monospace';
      ctx.fillText(String(student.nisn || '-'), 48 + (W - 96) * 0.25, infoY + 74, (W - 96) * 0.46);

      // Right column: Gender
      ctx.fillStyle = '#64748B';
      ctx.font = '700 14px "Plus Jakarta Sans", Arial, sans-serif';
      ctx.fillText('JENIS KELAMIN', 48 + (W - 96) * 0.75, infoY + 38);
      ctx.fillStyle = '#0F172A';
      ctx.font = '800 21px "Plus Jakarta Sans", Arial, sans-serif';
      ctx.fillText(student.gender === 'P' ? 'Perempuan' : 'Laki-laki', 48 + (W - 96) * 0.75, infoY + 74);

      // 9. QR Token Code Caption
      ctx.fillStyle = '#1E5BF0';
      ctx.font = '700 16px Consolas, "Courier New", monospace';
      ctx.fillText(String(student.qr_token || ''), W / 2, 932);

      // 10. Bottom Royal Blue Footer Bar
      ctx.fillStyle = '#1E5BF0';
      ctx.fillRect(0, H - 56, W, 56);
      ctx.fillStyle = '#FFFFFF';
      ctx.font = '700 14px "Plus Jakarta Sans", Arial, sans-serif';
      ctx.fillText('SCAN QR CODE UNTUK ABSENSI  •  ' + SCHOOL_INFO.appName.toUpperCase(), W / 2, H - 23);

      // Outer subtle border
      ctx.strokeStyle = '#E2E8F0';
      ctx.lineWidth = 2;
      ctx.strokeRect(1, 1, W - 2, H - 2);
    }

    function downloadSingleCard(idx) {
      const st = CARD_STUDENTS[idx];
      const canvas = document.getElementById('cardCanvas_' + idx);
      if (!st || !canvas) return;
      const filename = `Kartu_QR_${safeFilename(st.class_name)}_${safeFilename(st.name)}_${safeFilename(st.nisn)}.png`;
      canvas.toBlob(function (blob) {
        if (!blob) return AppModal.showError('Gagal membuat gambar PNG kartu.');
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
      }, 'image/png', 1.0);
    }

    async function downloadAllCards() {
      if (!CARD_STUDENTS.length) return;
      if (CARD_STUDENTS.length === 1) {
        return downloadSingleCard(0);
      }
      if (typeof JSZip === 'undefined') {
        return AppModal.showError('Library JSZip belum termuat. Periksa koneksi internet Anda.');
      }

      AppModal.showLoading('Menyiapkan Semua Gambar Kartu...', `Mengemas 0 / ${CARD_STUDENTS.length} gambar PNG ukuran KTP...`);
      try {
        const zip = new JSZip();
        for (let i = 0; i < CARD_STUDENTS.length; i++) {
          const st = CARD_STUDENTS[i];
          const canvas = document.getElementById('cardCanvas_' + i);
          if (!canvas) continue;
          const blob = await new Promise(res => canvas.toBlob(res, 'image/png', 1.0));
          const folderName = 'Kelas_' + safeFilename(st.class_name);
          const fileName = `${safeFilename(st.nisn)}_${safeFilename(st.name)}.png`;
          zip.folder(folderName).file(fileName, blob);
          $('#appModalLoadingSub').text(`Mengemas ${i + 1} / ${CARD_STUDENTS.length} gambar PNG...`);
        }

        $('#appModalLoadingSub').text('Membuat file arsip ZIP...');
        const zipBlob = await zip.generateAsync({ type: 'blob' });
        const zipName = `Kartu_QR_Siswa_${safeFilename(SCHOOL_INFO.slug)}${FILTER_CLASS ? '_Kelas_' + safeFilename(FILTER_CLASS) : ''}.zip`;
        const url = URL.createObjectURL(zipBlob);
        const a = document.createElement('a');
        a.href = url;
        a.download = zipName;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 2000);

        AppModal.showSaved(
          `Berhasil mengunduh ${CARD_STUDENTS.length} kartu siswa ukuran KTP Portrait (638×1012 px PNG) dalam file ${zipName}.`,
          'Download Semua Berhasil!'
        );
      } catch (err) {
        AppModal.showError('Gagal mengemas file ZIP: ' + (err.message || err));
      }
    }

    // Render all canvases on load
    $(async function () {
      if (!CARD_STUDENTS.length) return;
      AppModal.showLoading('Merender Kartu QR Siswa...', `Menyiapkan ${CARD_STUDENTS.length} kartu ukuran KTP (300 DPI)...`);
      // Prefer favicon (square icon) for compact square logo badge, fallback to logoUrl
      const brandImg = (await loadImageSafe(SCHOOL_INFO.faviconUrl)) || (await loadImageSafe(SCHOOL_INFO.logoUrl));
      for (let i = 0; i < CARD_STUDENTS.length; i++) {
        const canvas = document.getElementById('cardCanvas_' + i);
        if (canvas) {
          await renderStudentCard(canvas, CARD_STUDENTS[i], brandImg);
        }
      }
      AppModal.hideLoading();
    });
  </script>
</body>
</html>
