<html>
<head>
<style>
pre {
background-color: #EEE;
padding: 20px;
overflow: auto;
}
@media (min-width:640px) {
html {
padding: 0px 10%;
}

}

</style>
<meta name="viewport" property="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<p><br>
<center>
	<img src="https://xonotic.org/static/img/xonotic_logo_web.svg" style="max-width: 98%"><br>
<div style="padding: 20px; background-color: #EEFFEE; font-size: 14pt; display: inline-block; font-weight: bold">
Hi. You can clone this Xonotic server from my backup on a free ($0) cloud server with 24GB RAM and 4 dedicated Ampere/ARM cores and 600Mbit network.<br>
Check out the instructions below, <a href="https://xonotic.org/" target="_blank">Xonotic</a> and the <a href="https://extreme.voltage.nz/" target="_blank">Extreme Voltage</a> clan page.
</div> <p><br>
The dynamic DNS provider I use to get a "us.to" subdomain is <a href="http://afraid.org">Afraid.org</a>.<p><br>
    <div style="width: 100%"><canvas id="stats"></canvas></div>
</center>

    <!-- <script type="module" src="dimensions.js"></script> -->
    <script src="./chartjs/dist/chart.umd.js" type="module"></script>
    <script src="./d3.v7.min.js"></script>
<script type="module">

function convert_epoch(t) {
        const dt = new Date(parseInt(t)*1000);
        const hr = dt.getUTCHours();
        const m = "0" + dt.getUTCMinutes();

        return(hr + ':' + m.substr(-2));
}
d3.dsv(';', '<?php echo glob("stats-".gethostname().".csv")[0];?>').then(makeChart);

function makeChart(rdata)
{
	
let maxvalue = 0;

let data = rdata.slice(Math.max(0,(rdata.length-1440)), rdata.length);
data[0].mstolen = 0; 
for (var i = 1; i < data.length; i++) 
{ 
        data[i].mstolen = Math.max(0,data[i].stolen-data[i-1].stolen); 
        if(data[i].mstolen > maxvalue) maxvalue = data[i].mstolen;
}

for (var i = 0; i < data.length; i++) { data[i].clock = convert_epoch(data[i].time); }

//console.log(data);

	

const suggmax = Math.max(50, parseInt(maxvalue*(maxvalue >=1000?0.1:0.16))*10);
const loadfactor = Math.max(1,(Math.pow(10,(parseInt(suggmax)+"").length)*0.1));
new Chart(
    document.getElementById('stats'),
    {
      type: 'line',
      data: {
        labels: data.map(row => row.clock),
                datasets: [
                {
                    label: 'stolen cpu ticks',
                    data: data.map(row => row.mstolen),
                    fill: 'origin',
                },
                {
                    label: 'load average * '+loadfactor,
                    data: data.map(row => row.load*loadfactor),
                    fill: 'origin',
                  }

        ]
},
	options: {
	                elements: {
                    point:{
                        radius: 0
                    }
                },
        scales: {
        y: {
                suggestedMin: 0,
                suggestedMax: suggmax
            }
        }
    }
    }
  );
}
    </script>
<p><br>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require("Parsedown.php");
$Parsedown = new Parsedown();
//$Parsedown->setStrictMode(false);
echo $Parsedown->text(file_get_contents("README.md"));
?>
</body>
