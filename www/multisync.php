<!DOCTYPE html>
<html>
<head>
<?php
require_once("config.php");
require_once("common.php");
include 'common/menuHead.inc';

$advancedView = false;
if (isset($_GET['advancedView'])) {
	if ($_GET['advancedView'] == true || strtolower($_GET['advancedView'])  == "true") {
		$advancedView = true;
	} else {
		$advancedView = false;
	}
}
?>
<title><? echo $pageTitle; ?></title>
<script>
    var advancedView = <? echo $advancedView == true ? 'true' : 'false'; ?>;

    function updateMultiSyncRemotes(checkbox) {
		var remotes = "";

		if ($('#allRemotes').is(":checked")) {
			remotes = "255.255.255.255";

			$('input.remoteCheckbox').each(function() {
				if (($(this).is(":checked")) &&
						($(this).attr("name") != "255.255.255.255"))
				{
					$(this).prop('checked', false);
					if ($(checkbox).attr("name") != "255.255.255.255")
						DialogError("WARNING", "'All Remotes' is already checked.  Uncheck 'All Remotes' if you want to select individual FPP instances.");
				}
			});
		} else {
			$('input.remoteCheckbox').each(function() {
				if ($(this).is(":checked")) {
					if (remotes != "") {
						remotes += ",";
					}
					remotes += $(this).attr("name");
				}
			});
		}
        var inp = document.getElementById("extraMultiSyncRemotes");
        if (inp && inp.value) {
            if (remotes != "") {
                remotes += ",";
            }
            var str = inp.value;
            str = str.replace(/\s/g, '');
            remotes += str;
        }
        
        
		$.get("fppjson.php?command=setSetting&key=MultiSyncRemotes&value=" + remotes
		).done(function() {
			settings['MultiSyncRemotes'] = remotes;
            if (remotes == "") {
                $.jGrowl("Remote List Cleared.  You must restart fppd for the changes to take effect.");
            } else {
                $.jGrowl("Remote List set to: '" + remotes + "'.  You must restart fppd for the changes to take effect.");
            }

            //Mark FPPD as needing restart
            $.get('fppjson.php?command=setSetting&key=restartFlag&value=1');
            settings['restartFlag'] = 1;
            //Get the resart banner showing
            CheckRestartRebootFlags();
        }).fail(function() {
			DialogError("Save Remotes", "Save Failed");
		});

	}

	function getFPPSystemStatus(ip) {
		$.get("fppjson.php?command=getFPPstatus&ip=" + ip + (advancedView == true ? '&advancedView=true' : '')
		).done(function(data) {
			var status = 'Idle';
			var statusInfo = "";
			var elapsed = "";
			var files = "";

			if (data.status_name == 'playing')
			{
				status = 'Playing';

				elapsed = data.time_elapsed;

				if (data.current_sequence != "")
				{
					files += data.current_sequence;
					if (data.current_song != "")
						files += "<br>" + data.current_song;
				}
				else
				{
					files += data.current_song;
				}
			}
			else if (data.status_name == 'updating')
			{
				status = 'Updating';
			}
			else if (data.status_name == 'stopped')
			{
				status = 'Stopped';
			}
            else if (data.status_name == 'unknown')
            {
                status = '-';
                if (typeof(data.reason) !== 'undefined'){
                    DialogError("Get FPP System Status", "Get Status Failed for " + ip + "\n " + data.reason);
                } else {
                    DialogError("Get FPP System Status", "Get Status Failed for " + ip);
                }
            }
			else if (data.status_name == 'idle')
			{
				if (data.mode_name == 'remote')
				{
					if ((data.sequence_filename != "") ||
						(data.media_filename != ""))
					{
						status = 'Syncing';

						elapsed += data.time_elapsed;

						if (data.sequence_filename != "")
						{
							files += data.sequence_filename;
							if (data.media_filename != "")
								files += "<br>" + data.media_filename;
						}
						else
						{
							files += data.media_filename;
						}
					}
				}
            } else {
                status = data.status_name;
            }

			var rowID = "fpp_" + ip.replace(/\./g, '_');
            var auto_updates_stirng = "";

			$('#' + rowID + '_status').html(status);
			$('#' + rowID + '_elapsed').html(elapsed);
			$('#' + rowID + '_files').html(files);
			//Expert View Rows
            if(advancedView === true && data.status_name !== 'unknown') {
                $('#' + rowID + '_platform').html(data.advancedView.Platform + "<br><small class='hostDescriptionSM'>" + data.advancedView.Variant + "</small>");
                $('#advancedViewVersion_' + rowID).html(data.advancedView.Version);
                //$('#advancedViewBranch_' + rowID).html(data.advancedView.Branch);

                $('#advancedViewGitVersions_' + rowID).html("R: " + (typeof (data.advancedView.RemoteGitVersion) !== 'undefined' ? data.advancedView.RemoteGitVersion : 'Unknown') + "<br>L: " + (typeof (data.advancedView.LocalGitVersion) !== 'undefined' ? data.advancedView.LocalGitVersion : 'Unknown'));
                //Generate autoupdate status
                auto_updates_stirng = ((data.advancedView.AutoUpdatesDisabled === true ? "Disabled" : "Enabled"));
                //Work out if there is a Git version difference
                if (((typeof (data.advancedView.RemoteGitVersion) !== 'undefined' && typeof (data.advancedView.LocalGitVersion) !== 'undefined')) && data.advancedView.RemoteGitVersion !== "Unknown") {
                    if (data.advancedView.RemoteGitVersion !== data.advancedView.LocalGitVersion) {
                        auto_updates_stirng = auto_updates_stirng + "<br>" + '<a class="updateAvailable" href="http://' + ip + '/about.php" target="_blank">Update Available!</a>';
                    }
                }
                $('#advancedViewAutoUpdateState_' + rowID).html(auto_updates_stirng);

                $('#advancedViewUtilization_' + rowID).html("CPU: " + (typeof (data.advancedView.Utilization) !== 'undefined' ? Math.round((data.advancedView.Utilization.CPU) * 100) : 'Unk.') + "%" +
                    "<br>" +
                    "Mem: " + (typeof (data.advancedView.Utilization) !== 'undefined' ? Math.round(data.advancedView.Utilization.Memory) : 'Unk.') + "%" +
                    "<br>" +
                    "Uptime: " + (typeof (data.advancedView.Utilization) !== 'undefined' ? data.advancedView.Utilization.Uptime : 'Unk.'));
            }
		}).fail(function() {
			DialogError("Get FPP System Status", "Get Status Failed for " + ip + " via getFPPstatus");
		}).always(function() {
			if ($('#MultiSyncRefreshStatus').is(":checked"))
				setTimeout(function() {getFPPSystemStatus(ip);}, 1000);
		});
	}

	function parseFPPSystems(data) {
		$('#fppSystems tbody').empty();

		var remotes = [];
		if (typeof settings['MultiSyncRemotes'] === 'string') {
			var tarr = settings['MultiSyncRemotes'].split(',');
			for (var i = 0; i < tarr.length; i++) {
				remotes[tarr[i]] = 1;
			}
		}

		if (settings['fppMode'] == 'master') {
			$('#masterLegend').show();

			var star = "<input id='allRemotes' type='checkbox' class='remoteCheckbox' name='255.255.255.255'";
            if (typeof remotes["255.255.255.255"] !== 'undefined') {
				star += " checked";
                delete remotes[data[i].IP];
            }
			star += " onClick='updateMultiSyncRemotes(this);'>";

			var newRow = "<tr>" +
				"<td align='center'>" + star + "</td>" +
				"<td>ALL Remotes</td>" +
				"<td>255.255.255.255</td>" +
				"<td>ALL</td>" +
				"<td>Remote</td>" +
				"</tr>";
			$('#fppSystems tbody').append(newRow);
		}

		for (var i = 0; i < data.length; i++) {
			var star = "";
			var link = "";
			var ip = data[i].IP;
            var hostDescription = data[i].HostDescription;

			if (data[i].Local)
			{
				link = data[i].HostName;
				star = "*";
			} else {
				link = "<a href='http://" + data[i].IP + "/'>" + data[i].HostName + "</a>";
				if ((settings['fppMode'] == 'master') &&
						(data[i].fppMode == "remote"))
				{
					star = "<input type='checkbox' class='remoteCheckbox' name='" + data[i].IP + "'";
                    if (typeof remotes[data[i].IP] !== 'undefined') {
						star += " checked";
                        delete remotes[data[i].IP];
                    }
					star += " onClick='updateMultiSyncRemotes();'>";
				}
			}

			var fppMode = 'Player';
			if (data[i].fppMode == 'bridge')
				fppMode = 'Bridge';
			else if (data[i].fppMode == 'master')
				fppMode = 'Master';
			else if (data[i].fppMode == 'remote')
				fppMode = 'Remote';

			var rowID = "fpp_" + ip.replace(/\./g, '_');

			var newRow = "<tr id='" + rowID + "'>" +
				"<td align='center'>" + star + "</td>" +
				"<td>" + link + "<br><small class='hostDescriptionSM'>"+ hostDescription +"</small></td>" +
				"<td>" + data[i].IP + "</td>" +
                "<td id='" + rowID + "_platform'>" + data[i].Platform + "</td>" +
				"<td>" + fppMode + "</td>" +
				"<td id='" + rowID + "_status' align='center'></td>" +
				"<td id='" + rowID + "_elapsed'></td>" +
				"<td id='" + rowID + "_files'></td>";

            if (advancedView === true) {
                newRow = newRow + "<td class='advancedViewRowSpacer'></td>" +
                    "<td id='advancedViewVersion_" + rowID + "' class='advancedViewRow'></td>" +
                    //"<td id='advancedViewBranch_" + rowID + "'  class='advancedViewRow'></td>" +
                    "<td id='advancedViewGitVersions_" + rowID + "'  class='advancedViewRow'></td>" +
                    "<td id='advancedViewAutoUpdateState_" + rowID + "' class='advancedViewRow'></td>" +
                    "<td id='advancedViewUtilization_" + rowID + "'  class='advancedViewRow'></td>";
            }

            newRow = newRow + "</tr>";
			$('#fppSystems tbody').append(newRow);

			getFPPSystemStatus(ip);
		}
        var extras = "";
        for (var x in remotes) {
            if (extras != "") {
                extras += ",";
            }
            extras += x;
        }
        var inp = document.getElementById("extraMultiSyncRemotes");
        inp.value = extras;
	}

	function getFPPSystems() {
		$('#masterLegend').hide();
		$('#fppSystems tbody').empty();
		$('#fppSystems tbody').append("<tr><td colspan=5 align='center'>Loading...</td></tr>");

		$.get("fppjson.php?command=getSetting&key=MultiSyncRemotes", function(data) {
			settings['MultiSyncRemotes'] = data.MultiSyncRemotes;
			$.get("fppjson.php?command=getFPPSystems", function(data) {
				parseFPPSystems(data);
			});
		});
	}

	function refreshFPPSystems() {
		setTimeout(function() { getFPPSystems(); }, 1000);
	}

</script>
<style>
#fppSystems{
	border: 1px;
}

.masterHeader{
	width: 15%;
}

.masterValue{
	width: 40%;
}

.masterButton{
	text-align: right;
	width: 25%;
}
</style>
</head>
<body>
<div id="bodyWrapper">
	<?php include 'menu.inc'; ?>
	<br/>
	<div id="uifppsystems" class="settings">
		<fieldset>
			<legend>Discovered FPP Systems</legend>
			<table id='fppSystems' cellspacing='5'>
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th id="hostnameColumn">Hostname</th>
						<th>IP Address</th>
						<th>Platform</th>
						<th>Mode</th>
						<th>Status</th>
						<th>Elapsed</th>
						<th>File(s)</th>
						<?php
                        //Only show expert view is requested
						if ($advancedView == true) {
							?>
                            <th class="advancedViewHeaderSpacer"></th>
                            <th class="advancedViewHeader">Version</th>
                            <!--<th class="advancedViewHeader">Branch</th> -->
                            <th class="advancedViewHeader">Git Version(s)</th>
                            <th class="advancedViewHeader">Auto Updates</th>
                            <th class="advancedViewHeader">Utilization</th>
							<?php
						}
						?>
                    </tr>
				</thead>
				<tbody>
					<tr><td colspan=5 align='center'>Loading...</td></tr>
				</tbody>
			</table>
			<hr>
<?php
if ($settings['fppMode'] == 'master')
{
?>
			Additional MultiSync Remote IPs (comma separated): (For non-discoverable remotes)
            <input type="text" id="extraMultiSyncRemotes" maxlength="255" size="60" onchange='updateMultiSyncRemotes(null);' />

<br>
            CSV MultiSync Remote IP List (comma separated):
            <?
            $csvRemotes = "";
            if (isset($settings["MultiSyncCSVRemotes"])) {
                $csvRemotes = $settings["MultiSyncCSVRemotes"];
            }
            PrintSettingText("MultiSyncCSVRemotes", 1, 0, 255, 60, "", $csvRemotes); ?>
<br><br>
			<? PrintSettingCheckbox("Compress FSEQ files for transfer", "CompressMultiSyncTransfers", 0, 0, "1", "0"); ?> Compress FSEQ files during copy to Remotes to speed up file sync process<br>
<?php
}
?>
			<? PrintSettingCheckbox("Auto Refresh Systems Status", "MultiSyncRefreshStatus", 0, 0, "1", "0", "", "getFPPSystems"); ?> Auto Refresh status of FPP Systems<br>
            <?php
                if ($advancedView ==true) {
					?>
                    <b style="color: #FF0000; font-size: 0.9em;">**Expert View Active - Auto Refresh is not recommended as it may cause slowdowns</b>
                    <br>
                    <b style="color: #FF0000; font-size: 0.9em;">**Git Versions : </b> <b style="color: #FF0000; font-size: 0.9em;">R: - Remote Git Version</b> | <b style="color: #FF0000; font-size: 0.9em;">L: - Local Git Version</b><br>
					<?php
				}
            ?>
			<hr>
			<font size=-1>
				<span id='legend'>
				* - Local System
				<span id='masterLegend' style='display:none'><br>&#x2713; - Sync Remote FPP with this Master instance</span>
				</span>
			</font>
			<br>
                        <input type='button' class='buttons' value='Refresh' onClick='getFPPSystems();'>
<?php
if ($settings['fppMode'] == 'master')
{
?>
                        <input type='button' class='buttons' value='Sync Files' onClick='location.href="syncRemotes.php";'>
<?php
}
?>
                        <br>
            <br>
            <span><b>Views:</b></span>
            <br>
            <input type='button' class='buttons' value='Normal View'
                   onclick="window.open('/multisync.php','_self')">
            <br>
            <input type='button' class='buttons' value='Advanced View'
                   onclick="window.open('/multisync.php?advancedView=true','_self')">
		</fieldset>
	</div>
	<?php include 'common/footer.inc'; ?>
</div>

<script>

$('#MultiSyncCSVRemotes').on('change keydown paste input', function()
	{
		var key = 'MultiSyncCSVRemotes';
		var desc = $('#' + key).val();
		if (settings[key] != desc)
		{
			$.get('fppjson.php?command=setSetting&key=' + key + '&value=' + desc);
			settings[key] = desc;

            //Mark FPPD as needing restart
            $.get('fppjson.php?command=setSetting&key=restartFlag&value=1');
            settings['restartFlag'] = 1;
            //Get the resart banner showing
            CheckRestartRebootFlags();
        }
	});

$(document).ready(function() {
	getFPPSystems();
});

</script>


</body>
</html>
