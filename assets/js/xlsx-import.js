/**
 * Excel (.xlsx / .xls ONLY - strictly rejects .csv) reader & exporter using SheetJS.
 */
window.ExcelHelper = (function () {
  'use strict';

  function parseExcelFile(file) {
    return new Promise(function (resolve, reject) {
      if (!file) {
        return reject('Silakan pilih file Microsoft Excel (.xlsx / .xls).');
      }
      const name = (file.name || '').toLowerCase();
      if (name.endsWith('.csv')) {
        return reject('Format CSV tidak diperbolehkan! Harap gunakan file Microsoft Excel (.xlsx atau .xls).');
      }
      if (!name.endsWith('.xlsx') && !name.endsWith('.xls')) {
        return reject('Format file harus Microsoft Excel (.xlsx atau .xls).');
      }
      if (typeof XLSX === 'undefined') {
        return reject('Library pembaca Excel (SheetJS) belum termuat. Periksa koneksi internet Anda.');
      }

      const reader = new FileReader();
      reader.onload = function (e) {
        try {
          const data = new Uint8Array(e.target.result);
          const workbook = XLSX.read(data, { type: 'array' });
          const firstSheetName = workbook.SheetNames[0];
          const worksheet = workbook.Sheets[firstSheetName];
          const jsonRows = XLSX.utils.sheet_to_json(worksheet, { defval: '', raw: false });
          if (!jsonRows.length) {
            return reject('File Excel kosong atau tidak memiliki baris data.');
          }
          resolve(jsonRows);
        } catch (err) {
          reject('Gagal membaca file Excel: ' + (err.message || err));
        }
      };
      reader.onerror = function () {
        reject('Gagal membaca file dari perangkat.');
      };
      reader.readAsArrayBuffer(file);
    });
  }

  function exportToExcel(rows, filename, sheetName) {
    if (typeof XLSX === 'undefined') {
      AppModal.showError('Library SheetJS tidak tersedia untuk export Excel.');
      return;
    }
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, (sheetName || 'Data').substring(0, 31));
    XLSX.writeFile(wb, filename.endsWith('.xlsx') ? filename : filename + '.xlsx');
  }

  return {
    parseExcelFile: parseExcelFile,
    exportToExcel: exportToExcel
  };
})();
