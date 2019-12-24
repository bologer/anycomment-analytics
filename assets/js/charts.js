function queryData(url, callback) {
    jQuery.get(url).done(function (data) {
        callback(data);
    });
}

jQuery(document).ready(function ($) {

    var charts = $('[data-chart-root]');

    charts.each(function () {

        var rootEl = $(this),
            root = rootEl.data('chart-root') || '',
            type = rootEl.data('type') || 'line',
            url = rootEl.data('url') || '',
            rootClass = 'anycomment-chart',
            dateClass = 'anycomment-datepicker',
            canvasId = 'anycomment-analytics-' + root,
            loadingId = 'anycomment-loading-' + root,
            messageId = 'anycomment-message-' + root,
            localize = window.anycommentAnalytics || '';

        if (!root) {
            console.error("No root elements specified");
            return;
        }

        if (!url) {
            console.error("It is required to specify url");
            return;
        }

        var periods = {
                today: localize.periodToday,
                yesterday: localize.periodYesterday,
                week: localize.periodWeek,
                month: localize.periodMonth,
                quarter: localize.periodQuarter,
                year: localize.periodYear
            },
            periodKeys = Object.keys(periods),
            periodsLength = periodKeys.length,
            periodsHtml = '',
            i = 0;


        var entropy = (new Date()).getTime();

        for (; i < periodsLength; i++) {
            var periodKey = periodKeys[i],
                periodId = periodKey + '-' + entropy,
                isChecked = periodKey === 'month';

            periodsHtml += '<label for="' + periodId + '">' +
                '<input id="' + periodId + '" value="' + periodKey + '" ' + (isChecked ? 'checked="checked"' : '') + ' autocomplete="off" type="radio" name="period">' +
                '<span class="anycomment-chart__periods-button">' + periods[periodKeys[i]] + '</span>' +
                '</label>';
        }

        var html = '',
            onChange = 'onFormChange(this, \'' + root + '\', \'' + url + '\')';

        console.log(root, url, onChange);

        var dateIcon = '<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="calendar-alt"\n' +
            '     class="svg-inline--fa fa-calendar-alt fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">\n' +
            '    <path fill="currentColor"\n' +
            '          d="M148 288h-40c-6.6 0-12-5.4-12-12v-40c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v40c0 6.6-5.4 12-12 12zm108-12v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm96 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm-96 96v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm-96 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm192 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm96-260v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112c0-26.5 21.5-48 48-48h48V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h128V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h48c26.5 0 48 21.5 48 48zm-48 346V160H48v298c0 3.3 2.7 6 6 6h340c3.3 0 6-2.7 6-6z"></path>\n' +
            '</svg>';

        html += '<div class="anycomment-chart__controls">' +
            '<form onchange="' + onChange + '">' +
            '<div class="anycomment-chart__periods">' +
            periodsHtml +
            '</div>' +
            '<p>' + localize.customPeriod + '</p>' +
            '<div class="anycomment-chart__custom-periods">' +
            '<div class="anycomment-chart__custom-periods-field">' +
            '<input type="text" name="since" class="' + dateClass + '" placeholder="' + localize.fromDate + '"/>' +
            dateIcon +
            '</div>' +
            '<div class="anycomment-chart__custom-periods-field">' +
            '<input type="text" name="until" class="' + dateClass + '" placeholder="' + localize.toDate + '"/>' +
            dateIcon +
            '</div>' +
            '</div>' +
            '</form>' +
            '</div>' +
            '<div id="' + messageId + '" style="display: none;"></div>' +
            '<div id="' + loadingId + '">' + localize.loading + '</div>' +
            '<canvas id="' + canvasId + '"></canvas>';

        rootEl.addClass(rootClass);
        rootEl.html(html);

        var loadingEl = $('#' + loadingId);

        queryData(url, function (data) {
            var chart = window.Chart;

            chart.defaults.global.defaultFontColor = '#c8c8c8';
            chart.defaults.global.defaultFontFamily = 'Roboto, Verdana, sans-serif';
            chart.defaults.global.defaultFontSize = 18;
            chart.defaults.global.legend.position = 'bottom';

            loadingEl.fadeOut(300);

            if (!('graphs' in window)) {
                window.graphs = {};
            }

            if (data.message) {
                showMessage(root, data.message);
            } else {
                hideMessage(root);
            }

            window.graphs[root] = new chart(document.getElementById(canvasId).getContext('2d'), {
                type: type,
                data: data.chart,
                options: get_chart_options(type),
            });
        });
    });
});

/**
 * On form change, update graph data.
 *
 * @param form
 * @param {String} root
 * @param {String} url
 */
function onFormChange(form, root, url) {

    if (!form || !root || !url) {
        return;
    }

    console.log('form url', url);

    var formSerialized = $(form).serialize(),
        paramsUrl = url;

    if (paramsUrl.indexOf('?') === -1) {
        paramsUrl += formSerialized;
    } else {
        paramsUrl += '&' + formSerialized;
    }

    console.log('sending request to', paramsUrl);

    showLoader(root);

    queryData(paramsUrl, function (data) {

        hideLoader(root);

        if (data.message) {
            showMessage(root, data.message);
        } else {
            hideMessage(root);
        }

        window.graphs[root].data = data.chart;
        window.graphs[root].update();
    });
}

/**
 * Show message.
 * @param {String} root Root element.
 * @param {String} message Message to be show.
 */
function showMessage(root, message) {
    var id = '#anycomment-message-' + root,
        el = $(id);

    if (!el.length) {
        console.error(`Message element ${id} does not exist`);
    }

    el.show();
    el.html(message);
}

/**
 * Hide message.
 *
 * @param root
 */
function hideMessage(root) {
    var id = '#anycomment-message-' + root,
        el = $(id);

    if (!el.length) {
        console.error(`Message element ${id} does not exist`);
    }

    el.html('');
    el.hide();
}

/**
 * Show loader.
 *
 * @param root
 */
function showLoader(root) {
    var id = '#anycomment-loading-' + root,
        el = $(id);

    if (!el.length) {
        console.error(`Loader element ${id} does not exist`);
    }

    el.show();
}

/**
 * Hide loader.
 * @param root
 */
function hideLoader(root) {
    var id = '#anycomment-loading-' + root,
        el = $(id);

    if (!el.length) {
        console.error(`Loader element ${id} does not exist`);
    }

    el.hide();
}

function get_chart_options(option) {
    var options = {};

    switch (option) {
        case 'line':
            options = get_line_options();
            break;

        case 'bar':
            options = get_bar_options();
            break;
        case 'pie':
            options = get_pie_options();
            break;

        default:
            break;
    }

    return options;
}

function get_pie_options() {
    return {}
}

function get_bar_options() {
    return {
        legend: {
            display: true,
            labels: {
                fontColor: '#686868',
            }
        },
        layout: {
            fontSize: 10,
            padding: {
                left: 0,
                right: 0,
                top: 0,
                bottom: 0
            }
        },
        scales: {
            xAxes: [{
                display: false,
                gridLines: {
                    display: true,
                    color: '#E8EDEF',
                    barPercentage: 0.4
                },
            }],
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    scaleBeginAtZero: true,
                    padding: 50,
                },
                gridLines: {
                    display: true,
                    zeroLineColor: '#ffffff',
                    drawBorder: false,
                },
            }]
        }
    };
}

function get_line_options() {
    return {
        layout: {
            padding: {
                left: 10,
                right: 10,
                top: 0,
                bottom: 0
            }
        },
        scales: {
            scales: {
                xAxes: [{
                    type: 'time',
                    time: {
                        displayFormats: {
                            'hour': 'MMM YYYY'
                        }
                    },
                    ticks: {
                        beginAtZero: false,
                        autoskip: true,
                        callback: function (tick, index) {
                            return (index % 3) ? "" : tick;
                        }
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        scaleBeginAtZero: true,
                    }
                }]
            },
        },
        responsive: true,
    };
}

/**
 * Normalize list of databasets or single one.
 * @param datasets
 */
function normalizeDatasets(datasets) {
    if (datasets.length !== undefined) {
        for (var i = 0; i < datasets.length; i++) {
            datasets[i] = normalizeDataset(datasets[i]);
        }
    } else {
        datasets = normalizeDataset(datasets);
    }

    return datasets;
}

/**
 * Normalize provided dataset.
 * @param dataset
 * @returns {*}
 */
function normalizeDataset(dataset) {
    var normalizedDataset = dataset;

    var defaultOptions = [];
    defaultOptions['fill'] = false;
    defaultOptions['borderColor'] = '#f1b927';
    defaultOptions['borderWidth'] = 5;
    defaultOptions['lineTension'] = 0;
    defaultOptions['borderJoinStyle'] = 'miter';
    defaultOptions['pointRadius'] = 0;
    defaultOptions['pointHitRadius'] = 30;

    if (dataset.label === undefined) {
        dataset.label = false;
    }

    Object.keys(defaultOptions).forEach(function (key) {
        if (normalizedDataset[key] === undefined) {
            normalizedDataset[key] = defaultOptions[key];
        }
    });

    return normalizedDataset;
}