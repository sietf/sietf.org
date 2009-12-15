<?

require_once('shoutcast_class.php');

$display_array = array("Stream Title", "Stream Genre", "Stream URL", "Current Song", 
"Server Status", "Stream Status", "Listener Peak", "Average Listen Time", "Stream Title", 
"Content Type", "Stream Genre", "Stream URL", "Current Song");

$radio = new Radio("sietf.org:8000");
$data = $radio->getServerInfo($display_array);
$status = $data[4];
$online = strstr($status, "currently up") ? True : False;

if ($online) {
  $stream_name = $data[0];
  $song = $data[12];
  $arr = array('online' => $online, 'streamName' => $stream_name, 'song' => $song);
} else {
  $arr = array('online' => $online);
}

echo json_encode($arr);
?>
