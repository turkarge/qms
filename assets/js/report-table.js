(function () {
    'use strict';

    function normalize(value) {
        return (value || '').trim();
    }

    function tableRows(table) {
        return table && table.tBodies[0] ? Array.from(table.tBodies[0].rows).filter((row) => row.cells.length > 1) : [];
    }

    function cellValue(row, index, type) {
        const cell = row.cells[index];
        const raw = normalize((cell && cell.dataset.sort) || (cell && cell.innerText) || '');

        if (type === 'date') {
            if (!raw || raw === '-') {
                return 0;
            }
            return Date.parse(raw.replace(' ', 'T')) || 0;
        }

        if (type === 'number') {
            const parsed = Number(raw.replace(',', '.'));
            return Number.isFinite(parsed) ? parsed : 0;
        }

        return raw.toLocaleLowerCase('tr-TR');
    }

    function visibleRows(table) {
        return tableRows(table).filter((row) => row.style.display !== 'none');
    }

    function exportExcel(table, filename) {
        const clone = table.cloneNode(true);
        const sourceRows = tableRows(table);
        clone.querySelectorAll('.d-print-none').forEach((element) => element.remove());
        Array.from(clone.tBodies[0].rows).forEach((row, index) => {
            if (sourceRows[index] && sourceRows[index].style.display === 'none') {
                row.remove();
            }
        });

        const html = '\ufeff' + clone.outerHTML;
        const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename || 'report.xls';
        link.click();
        URL.revokeObjectURL(link.href);
    }

    function emailRows(table, subject) {
        const body = visibleRows(table)
            .slice(0, 80)
            .map((row) => Array.from(row.cells)
                .filter((cell) => !cell.classList.contains('d-print-none'))
                .map((cell) => normalize(cell.innerText).replace(/\s+/g, ' '))
                .join(' | '))
            .join('\n');

        window.location.href = 'mailto:?subject=' + encodeURIComponent(subject || document.title) + '&body=' + encodeURIComponent(body);
    }

    function initReportTable(table) {
        const tbody = table.tBodies[0];
        if (!tbody || table.dataset.reportTableInitialized === '1') {
            return;
        }

        table.dataset.reportTableInitialized = '1';
        let sortState = { index: -1, dir: 1 };

        table.querySelectorAll('th').forEach((th, index) => {
            if (th.dataset.sortable === 'false') {
                return;
            }

            th.classList.add('cursor-pointer', 'kirpi-sortable-th');
            th.addEventListener('click', () => {
                const type = th.dataset.type || 'text';
                const rows = tableRows(table);
                sortState.dir = sortState.index === index ? sortState.dir * -1 : 1;
                sortState.index = index;

                table.querySelectorAll('th').forEach((header) => header.removeAttribute('data-sort-dir'));
                th.setAttribute('data-sort-dir', sortState.dir === 1 ? 'asc' : 'desc');

                rows.sort((a, b) => {
                    const av = cellValue(a, index, type);
                    const bv = cellValue(b, index, type);
                    return av > bv ? sortState.dir : av < bv ? -sortState.dir : 0;
                });

                rows.forEach((row) => tbody.appendChild(row));
            });
        });
    }

    function initReportPage(root = document) {
        const table = root.querySelector('.kirpi-sortable-report');
        if (!table) {
            return;
        }

        initReportTable(table);

        const search = root.querySelector('.js-kirpi-report-search');
        if (search && search.dataset.reportSearchInitialized !== '1') {
            search.dataset.reportSearchInitialized = '1';
            search.addEventListener('input', () => {
                const query = search.value.toLocaleLowerCase('tr-TR');
                tableRows(table).forEach((row) => {
                    row.style.display = row.innerText.toLocaleLowerCase('tr-TR').includes(query) ? '' : 'none';
                });
            });
        }

        const printButton = root.querySelector('.js-kirpi-report-print');
        if (printButton && printButton.dataset.reportPrintInitialized !== '1') {
            printButton.dataset.reportPrintInitialized = '1';
            printButton.addEventListener('click', () => window.print());
        }

        const emailButton = root.querySelector('.js-kirpi-report-email');
        if (emailButton && emailButton.dataset.reportEmailInitialized !== '1') {
            emailButton.dataset.reportEmailInitialized = '1';
            emailButton.addEventListener('click', (event) => {
                emailRows(table, event.currentTarget.dataset.subject || table.dataset.reportTitle || document.title);
            });
        }

        const excelButton = root.querySelector('.js-kirpi-report-excel');
        if (excelButton && excelButton.dataset.reportExcelInitialized !== '1') {
            excelButton.dataset.reportExcelInitialized = '1';
            excelButton.addEventListener('click', (event) => {
                exportExcel(table, event.currentTarget.dataset.filename || 'report.xls');
            });
        }
    }

    window.KirpiReportTable = {
        init: initReportPage
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initReportPage());
    } else {
        initReportPage();
    }
})();
