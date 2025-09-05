<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Publish Reports Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet" />
    <style>
        pre.json-data {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            max-height: 300px;
            overflow: auto;
        }
    </style>
</head>

<body>
    <div class="container my-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title mb-4">Publish Reports Viewer</h4>
                <form id="filterForm" class="row g-3">
                    <div class="row">
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <label class="input-group-text" for="year">Year</label>
                                        <select class="form-select" name="year" id="year"></select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <label class="input-group-text" for="month">Month</label>
                                        <select class="form-select" name="month" id="month"></select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <label class="input-group-text" for="lang">Language</label>
                                        <select class="form-select" name="lang" id="lang"></select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- <div class="col-md-3">
                            <div class="input-group">
                                <label class="input-group-text" for="sourcetitle">Source Title</label>
                                <select class="form-select" name="sourcetitle" id="sourcetitle"></select>
                            </div>
                        </div> -->
                        <div class="col-md-3">
                            <div class="input-group">
                                <label class="input-group-text" for="user">User</label>
                                <select class="form-select" name="user" id="user"></select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <div class="input-group">
                                <label class="input-group-text" for="result">Result</label>
                                <select class="form-select" name="result" id="result"></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-outline-secondary w-100" id="resetBtn">Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="loading" class="text-center my-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div class="card mt-4 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="resultsTable" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th style="display:none">ID</th>
                                <th>Date</th>
                                <th>Title</th>
                                <th>User</th>
                                <th>Language</th>
                                <th>Source Title</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailsModalLabel">Data Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <pre class="json-data" id="modalData"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let allResults = [];
        let originalResults = []; // لتخزين البيانات الأصلية قبل التجميع

        function populateFilterOptions(results) {
            const unique = (key, transform = val => val) => {
                return [...new Set(results.map(item => transform(item[key])))].filter(Boolean).sort();
            };

            const options = {
                year: unique('date', d => d.split('-')[0]),
                month: unique('date', d => d.split('-')[1]),
                // sourcetitle: unique('sourcetitle'),
                user: unique('user'),
                lang: unique('lang'),
                result: unique('result')
            };

            for (const [id, values] of Object.entries(options)) {
                const select = document.getElementById(id);
                select.innerHTML = '<option value="">-- All --</option>' +
                    values.map(value => `<option value="${value}">${value}</option>`).join('');
            }
        }

        function showDetails(id) {
            // Search in original data
            const result = originalResults.find(row => row.id == id);
            if (!result) {
                document.getElementById('modalData').textContent = 'Data not found.';
                return;
            }

            try {
                const json = typeof result.data === 'string' ? JSON.parse(result.data) : result.data;
                // Use textContent to prevent XSS
                const formattedJson = JSON.stringify(json, null, 2);
                document.getElementById('modalData').textContent = formattedJson;
                const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
                modal.show();
            } catch (e) {
                console.error('JSON parsing error:', e);
                document.getElementById('modalData').textContent = 'Unable to parse data.';
            }
        }

        $(document).ready(function() {
            // Load filters once only
            $.getJSON('/api/index.php?get=publish_reports')
                .done(function(json) {
                    if (json && json.results) {
                        populateFilterOptions(json.results);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Failed to load filter options:', error);
                    // Optionally show user-friendly error message
                });

            // إعداد DataTable
            let table = $('#resultsTable').DataTable({
                ajax: {
                    url: '/api/index.php?get=publish_reports',
                    data: function(d) {
                        const formData = $('#filterForm').serializeArray();
                        formData.forEach(field => {
                            if (field.value.trim()) {
                                d[field.name] = field.value;
                            }
                        });
                    },
                    dataSrc: function(json) {
                        // تخزين البيانات الأصلية كاملة
                        originalResults = json.results;

                        // تجميع الصفوف حسب الحقول المطلوبة
                        const grouped = {};
                        originalResults.forEach(item => {
                            const key = [
                                item.date.split(' ')[0], // فقط جزء التاريخ بدون الوقت
                                item.title,
                                item.user,
                                item.lang,
                                item.sourcetitle
                            ].join('|');

                            if (!grouped[key]) {
                                grouped[key] = {
                                    ...item,
                                    resultsArray: [item.result],
                                    idsArray: [item.id]
                                };
                            } else {
                                grouped[key].resultsArray.push(item.result);
                                grouped[key].idsArray.push(item.id);
                            }
                        });

                        allResults = Object.values(grouped);
                        return allResults;
                    }
                },
                columns: [{
                        data: 'id',
                        visible: false
                    },
                    {
                        data: 'date',
                        render: function(data, type) {
                            if (type === 'display' || type === 'filter') {
                                return data.split(' ')[0];
                            }
                            return data;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {

                            // Validate language code (basic validation)
                            const lang = /^[a-z]{2,3}$/.test(row.lang) ? row.lang : 'en';
                            const title = encodeURIComponent(row.title || '');
                            const escapedTitle = row.title || '';

                            if (!title) return escapedTitle;

                            return `<a href="https://${lang}.wikipedia.org/wiki/${title}" target="_blank" rel="noopener noreferrer">${escapedTitle}</a>`;
                        }
                    },
                    {
                        data: 'user'
                    },
                    {
                        data: 'lang'
                    },
                    {
                        data: 'sourcetitle'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            // Display multiple buttons for grouped results
                            return row.resultsArray.map((res, index) => {
                                const id = row.idsArray[index];
                                const escapedRes = res;
                                const safeId = parseInt(id, 10); // Ensure ID is numeric
                                return `<span class="badge bg-secondary" style="cursor:pointer; margin-right:4px;" onclick="showDetails(${safeId})">${escapedRes}</span>`;
                            }).join('');
                        }
                    }
                ],
                order: [
                    [1, 'desc']
                ]
            });

            // حدث إرسال الفورم
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // زر إعادة التهيئة
            $('#resetBtn').on('click', function() {
                $('#filterForm')[0].reset();
                table.ajax.reload();
            });
        });
    </script>


</body>

</html>
