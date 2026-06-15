document.addEventListener('DOMContentLoaded', function () {
    // 1. Message Volume Trend (Line Chart)
    var trendOptions = {
        series: [{
            name: 'Messages',
            data: [450, 680, 520, 890, 1100, 950, 1284]
        }],
        chart: {
            height: 350,
            type: 'area',
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#84cc16'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.05,
                stops: [20, 100, 100, 100]
            }
        },
        xaxis: {
            categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: { show: false },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4
        },
        tooltip: {
            x: { format: 'dd/MM/yy HH:mm' },
        },
    };

    var trendChart = new ApexCharts(document.querySelector("#messageTrendChart"), trendOptions);
    trendChart.render();

    // 2. DMs vs Channels (Donut Chart)
    var engagementOptions = {
        series: [65, 35],
        labels: ['Channels', 'Direct Messages'],
        chart: {
            type: 'donut',
            height: 300,
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#84cc16', '#3b82f6'],
        legend: {
            position: 'bottom',
            fontWeight: 600
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '75%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: function (w) {
                                return '100%'
                            }
                        }
                    }
                }
            }
        },
        dataLabels: { enabled: false }
    };

    var engagementChart = new ApexCharts(document.querySelector("#engagementChart"), engagementOptions);
    engagementChart.render();

    // 3. Peak Activity Hours (Bar Chart)
    var peakOptions = {
        series: [{
            name: 'Activity Level',
            data: [10, 20, 15, 45, 85, 100, 90, 70, 40, 25, 15, 10]
        }],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: {
                borderRadius: 8,
                columnWidth: '50%',
                distributed: true
            }
        },
        colors: ['#f1f5f9', '#ecfccb', '#bef264', '#a3e635', '#84cc16', '#65a30d', '#4d7c0f', '#3f6212', '#a3e635', '#bef264', '#ecfccb', '#f1f5f9'],
        dataLabels: { enabled: false },
        legend: { show: false },
        xaxis: {
            categories: ['8am', '9am', '10am', '11am', '12pm', '1pm', '2pm', '3pm', '4pm', '5pm', '6pm', '7pm'],
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: { show: false },
        grid: { show: false }
    };

    var peakChart = new ApexCharts(document.querySelector("#peakHoursChart"), peakOptions);
    peakChart.render();

    // 4. Member Growth (Line Chart)
    var memberOptions = {
        series: [{
            name: 'New Joins',
            data: [15, 28, 42, 35, 62, 85]
        }],
        chart: {
            height: 350,
            type: 'line',
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#3b82f6'],
        stroke: { width: 4, curve: 'smooth' },
        xaxis: {
            categories: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
        },
        markers: {
            size: 6,
            colors: ['#3b82f6'],
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: { size: 8 }
        },
        grid: {
            borderColor: '#f1f5f9',
        }
    };

    var memberChart = new ApexCharts(document.querySelector("#memberGrowthChart"), memberOptions);
    memberChart.render();

    // 5. File Distribution (Donut or Radial Bar)
    var fileOptions = {
        series: [44, 55, 67, 83],
        chart: {
            height: 350,
            type: 'radialBar',
        },
        plotOptions: {
            radialBar: {
                dataLabels: {
                    name: { fontSize: '22px' },
                    value: { fontSize: '16px' },
                    total: {
                        show: true,
                        label: 'Total',
                        formatter: function (w) {
                            return '12.4GB'
                        }
                    }
                }
            }
        },
        labels: ['Apples', 'Oranges', 'Bananas', 'Berries'],
        colors: ['#84cc16', '#3b82f6', '#f59e0b', '#ef4444'],
        labels: ['Images', 'Documents', 'Videos', 'Others'],
    };

    var fileChart = new ApexCharts(document.querySelector("#fileTypeChart"), fileOptions);
    fileChart.render();

    // 6. Pin Trend (Area Chart)
    var pinOptions = {
        series: [{
            name: 'Pins',
            data: [4, 8, 12, 10, 15, 22]
        }],
        chart: {
            height: 250,
            type: 'area',
            toolbar: { show: false },
        },
        colors: ['#f59e0b'],
        dataLabels: { enabled: false },
        stroke: { curve: 'straight', width: 2 },
        xaxis: {
            categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
            labels: { show: false }
        },
        yaxis: { show: false },
        grid: { show: false }
    };

    var pinChart = new ApexCharts(document.querySelector("#pinTrendChart"), pinOptions);
    pinChart.render();
});
