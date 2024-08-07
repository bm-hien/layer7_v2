<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Server statuS</title>
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<script type="text/javascript" src="//www.google.com/jsapi"></script>
	<script>

		/*****************************************
					Configurations
		*****************************************/

		/*
			nginx configs - get informations in nginx.conf
			maxConnections = worker_processes * worker_connections
			if worker_processes = auto, worker_processes = num of cpus
			for more informations read http://nginx.org/en/docs/ngx_core_module.html#worker_processes
		*/
		maxConnections = 1536;

		/* interface */
		var serverName = document.location.hostname;
		var serverStatusURL = '/nginx_status'
		var refreshInterval = 1; //seconds
		var itensInLineCharts = 60;

		/****************************************/

		var lastAccepts = null;
		var lastHandled = null;
		var lastRequests = null;

		var chartAccepts, dataAccepts, optionsChartAccepts = {
			height: 200,
			vAxis: { title: 'Accepts/s' },
			legend: { position: 'none' },
			hAxis: { format: 'HH:mm' }
		};
		var chartHandled, dataHandled, optionsChartHandled = {
			height: 200,
			vAxis: { title: 'Handled/s' },
			legend: { position: 'none' },
			hAxis: { format: 'HH:mm' }
		};
		var chartRequests, dataRequests, optionsChartRequests = {
			height: 200,
			vAxis: { title: 'Request/s' },
			legend: { position: 'none' },
			hAxis: { format: 'HH:mm' }
		};
		var chartScore, optionsChartScore = {
			height: 100,
			legend: { position: 'none' },
			bar: { groupWidth: '90%' },
			isStacked: true
		};

		function initData() {
			$("#serverName").html(serverName);

			chartAccepts = new google.visualization.LineChart($("#accepts").get(0));
			chartHandled = new google.visualization.LineChart($("#handled").get(0));
			chartRequests = new google.visualization.LineChart($("#requests").get(0));
			chartScore = new google.visualization.BarChart($("#score").get(0));

			dataAccepts = new google.visualization.DataTable();
			dataAccepts.addColumn('datetime', 'X');
			dataAccepts.addColumn('number', 'Accepts/s');

			dataHandled = new google.visualization.DataTable();
			dataHandled.addColumn('datetime', 'X');
			dataHandled.addColumn('number', 'Handled/s');

			dataRequests = new google.visualization.DataTable();
			dataRequests.addColumn('datetime', 'X');
			dataRequests.addColumn('number', 'Request/s');

			loadData();
		}
		function loadData() {
			$.get(serverStatusURL + "?" + (new Date().getTime()), function (status) {

				var x = status.match(/accepts\s+handled\s+requests\s+(\d+)\s+(\d+)\s+(\d+)\s+Reading:\s+(\d+)\s+Writing:\s+(\d+)\s+Waiting:\s+(\d+)/);

				var accepts = parseInt(x[1]);
				var handled = parseInt(x[2]);
				var requests = parseInt(x[3]);
				var reading = parseInt(x[4]);
				var writing = parseInt(x[5]);
				var waiting = parseInt(x[6]);

				$('#totalAccepts').html(parseInt(accepts / 1000) + 'M');
				$('#totalHandled').html(parseInt(handled / 1000) + 'M');
				$('#totalRequests').html(parseInt(requests / 1000) + 'M');

				if (lastAccepts != null) {
					if (dataAccepts.getNumberOfRows() > itensInLineCharts)
						dataAccepts.removeRow(0);
					console.log(accepts, lastAccepts)
					dataAccepts.addRow([new Date(), parseFloat(parseFloat((accepts - lastAccepts) / refreshInterval).toFixed(1))]);
				}
				optionsChartAccepts.width = $("#accepts").innerWidth();
				chartAccepts.draw(dataAccepts, optionsChartAccepts);
				lastAccepts = accepts;

				if (lastHandled != null) {
					if (dataHandled.getNumberOfRows() > itensInLineCharts)
						dataHandled.removeRow(0);
					dataHandled.addRow([new Date(), parseFloat(parseFloat((handled - lastHandled) / refreshInterval).toFixed(1))]);
				}
				optionsChartHandled.width = $("#handled").innerWidth();
				chartHandled.draw(dataHandled, optionsChartHandled);
				lastHandled = handled;

				if (lastRequests != null) {
					if (dataRequests.getNumberOfRows() > itensInLineCharts)
						dataRequests.removeRow(0);
					dataRequests.addRow([new Date(), parseFloat(parseFloat((requests - lastRequests) / refreshInterval).toFixed(1))]);
				}
				optionsChartRequests.width = $("#requests").innerWidth();
				chartRequests.draw(dataRequests, optionsChartRequests);
				lastRequests = requests;

				var dataScore = google.visualization.arrayToDataTable([
					[
						'Item',
						'Reading',
						'Writing',
						'Waiting'
					],
					[
						'Score',
						reading,
						writing,
						waiting
					]
				]);
				optionsChartScore.width = $("#score").innerWidth();
				optionsChartScore.max = maxConnections;
				chartScore.draw(dataScore, optionsChartScore);

				setTimeout(loadData, refreshInterval * 1000);

			}, 'text');
		}

		google.load("visualization", "1", { packages: ['corechart', 'line', 'bar'] });
		google.setOnLoadCallback(initData);
	</script>
	<style>
		.copy {
			padding-top: 35px
		}
	</style>
</head>

<body>

	<div class="container">
		<div class="row">
			<div class="col-md-12">

				<h1 id="serverName" class="text-center"></h1>

				<div class="row">
					<div class="col-md-4 text-center">
						<h3><small>Total accepts</small>
							<div id="totalAccepts"></div>
						</h3>
					</div>
					<div class="col-md-4 text-center">
						<h3><small>Total handled</small>
							<div id="totalHandled"></div>
						</h3>
					</div>
					<div class="col-md-4 text-center">
						<h3><small>Total requests</small>
							<div id="totalRequests"></div>
						</h3>
					</div>
				</div>

				<div class="row">
					<div class="col-md-4">
						<div id="accepts" class="lines"></div>
					</div>
					<div class="col-md-4">
						<div id="handled" class="lines"></div>
					</div>
					<div class="col-md-4">
						<div id="requests" class="lines"></div>
					</div>
				</div>

				<div class="col-md-12">
					<div id="score"></div>
				</div>
			</div>
		</div>
		<div class="row">

		</div>
	</div>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
</body>

</html>
