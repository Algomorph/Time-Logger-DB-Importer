<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css"
          integrity="sha512-pg7xGkuHzhrV2jAMJvQsTV30au1VGlnxVN4sgmG8Yv0dxGR71B21QeHGLMvYod4AaygAzz87swLEZURw7VND2A=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

</head>
<body>
<!-- Create a div where the graph will take place -->
<div id="chart"></div>
<!--<div class="btn-group">-->
<!--    <button type="button" class="btn btn-outline-primary" id="hide-all">Hide All</button>-->
<!--    <button type="button" class="btn btn-outline-primary" id="show">Show</button>-->
<!--    <button type="button" class="btn btn-outline-primary" id="hide">Hide</button>-->
<!--</div>-->
<div id="tree"></div>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script
        src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
        crossorigin="anonymous">
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js"
        integrity="sha512-TGClBy3S4qrWJtzel4qMtXsmM0Y9cap6QwRm3zo1MpVjvIURa90YYz5weeh6nvDGKZf/x3hrl1zzHW/uygftKg=="
        crossorigin="anonymous" referrerpolicy="no-referrer">
</script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"
        integrity="sha384-7+zCNj/IqJ95wo16oMtfsKbZ9ccEh31eOz1HGyDuCQ6wgnyJNSYdrPa03rtR1zdB"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"
        integrity="sha384-QJHtvGhmr9XOIpI6YVutG+2QOK9T+ZnN4kzFN1RtK3zEFEIsxhlmWl5/YESvpZ13"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p"
        crossorigin="anonymous"></script>
<script>
    function minutesToHoursAndMinutes(value) {
        return Math.round(value / 60) + ":" + (value % 60).toString().padStart(2, "0")
    }

    function generateChart(seriesCollection, colorCollection) {
        // set up chart options
        const options = {
            series: seriesCollection,
            chart: {
                type: 'area',
                height: 920,
                stacked: true,
                events: {
                    selection: function (chart, e) {
                        console.log(new Date(e.xaxis.min))
                    }
                },
            },
            colors: colorCollection,
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 1
            },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.6,
                    opacityTo: 0.8,
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            },
            xaxis: {
                type: 'datetime'
            },
            yaxis: {
                labels: {
                    formatter: minutesToHoursAndMinutes
                },
            },
            tooltip: {
                y: {
                    formatter: minutesToHoursAndMinutes
                }
            }

        };

        const chart = new ApexCharts(document.querySelector("#chart"), options);
        chart.render();
        return chart;
    }

    function generateTree(names, chart) {
        const treeData = [];
        for (const name of names) {
            treeData.push({
                'text': name,
                'state': {
                    'opened': false,
                    'selected': true,
                    'checked': true
                },
                'children': []
            });
        }
        $('#tree').jstree({
            "core": {
                "animation": 0,
                "check_callback": true,
                "themes": {"stripes": true},
                'data': [
                    {
                        'text': 'Actions',
                        'state': {
                            'opened': false,
                            'selected': false
                        },
                        'children': treeData
                    }
                ]
            },
            "types": {
                "#": {
                    "max_children": 1,
                    "max_depth": 4,
                    "valid_children": ["root"]
                },
                "root": {
                    "icon": "https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default-dark/40px.png",
                    "valid_children": ["default"]
                },
                "default": {
                    "valid_children": ["default", "file"]
                },
                "file": {
                    "icon": "glyphicon glyphicon-file",
                    "valid_children": []
                }
            },
            "plugins": [
                "checkbox", "search"
            ]
        });
    }

    function handleTreeNode(show, node, tree, chart) {
        if (node.children.length === 0) {
            if (show) {
                chart.showSeries(node.original.text);
            } else {
                chart.hideSeries(node.original.text);
            }
        } else {
            for(const node_name of node.children){
                handleTreeNode(show, tree.get_node(node_name), tree, chart);
            }
        }
    }

    (function () {
        const dataUrl = "http://kramida.com/data/insertdb.php";
        const colorCollection = [];
        const seriesCollection = [];
        const seriesNames = [];
        const seriesMap = new Map();
        <?php
        if (isset($_GET['activities'])) {
            $activities = explode(",", $_GET['activities']);
            echo "const query_activities = new Set([";
            foreach ($activities as $activity) {
                echo "'" . $activity . "'" . ",";
            }
            echo "]);\n";
            echo "const limit_activities = true;\n";
        } else {
            echo "const query_activities = null;\n";
            echo "const limit_activities = false;\n";
        } ?>
        // prefetch the data types
        fetch(dataUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({'retrieve_activity_types': 1})
            }
        ).then(response => response.json()).then(
            activityTypes => {
                for (const type of activityTypes) {
                    let activityName = type.short_description;
                    if (limit_activities && !query_activities.has(activityName)) {
                        continue;
                    }
                    if (type.screen_used === "1") {
                        activityName += " (Screen)";
                    }
                    const activitySeries = {
                        name: activityName,
                        data: []
                    }
                    seriesNames.push(activityName);
                    seriesCollection.push(activitySeries);
                    colorCollection.push("#" + type.color);
                    seriesMap.set(activityName, activitySeries);
                }
                fetch(dataUrl).then(response => response.json()).then(
                    data => {
                        let minDate = Date.now()
                        let maxDate = Date.parse('01 Jan 1970 00:00:00 GMT')
                        for (const weeklyActionRecord of data) {
                            const date = Date.parse(weeklyActionRecord.week + " GMT-0500");
                            weeklyActionRecord.date = date;

                            if (date > maxDate) {
                                maxDate = date;
                            } else if (date < minDate) {
                                minDate = date;
                            }
                        }
                        const secondsPerWeek = 604800
                        const msPerWeek = secondsPerWeek * 1e3
                        const weekCount = (maxDate - minDate) / msPerWeek + 1
                        // initialize series to zeros
                        for (const activitySeries of seriesCollection) {
                            let currentDate = minDate;
                            for (let iWeek = 0; iWeek < weekCount; iWeek++) {
                                activitySeries.data.push([currentDate, 0]);
                                currentDate += msPerWeek;
                            }
                        }
                        // fill series entries
                        for (const weeklyActionRecord of data) {
                            let activityKey = weeklyActionRecord.activity;
                            if (limit_activities && !query_activities.has(activityKey)) {
                                continue;
                            }
                            if (weeklyActionRecord.screen === "1") {
                                activityKey += " (Screen)"
                            }
                            const series = seriesMap.get(activityKey);

                            const iWeek = Math.round((weeklyActionRecord.date - minDate) / msPerWeek);
                            series.data[iWeek][1] = weeklyActionRecord.duration;
                        }
                        const chart = generateChart(seriesCollection, colorCollection);
                        generateTree(seriesNames, chart);
                        $("#tree").on('changed.jstree', function (e, data) {
                           const tree = $.jstree.reference('#tree');
                           if(data.node !== undefined){
                               handleTreeNode(tree.is_checked(data.node), data.node, tree, chart);
                           }
                        });
                    }
                );
            }
        );
    })();

</script>
</body>

</html>