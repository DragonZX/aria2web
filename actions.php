<?php
defined( '_ARIA2WEB' ) or die();
/**
* @version		$Id$
* @package	aria2web
* @copyright	Copyright (C) 2010 soeren. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Aria2Web is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* http://sourceforge.net/projects/aria2web/
*/

require_once 'XML/RPC2/Client.php';

$aria2_url = 'http://';
if( $aria2_parameters['xml_rpc_user'] != '') {
	$aria2_url .= $aria2_parameters['xml_rpc_user'].':'.$aria2_parameters['xml_rpc_pass'].'@';
}
$aria2_url .= $aria2_xmlrpc_host.':'.$aria2_parameters['xml_rpc_listen_port']. $aria2_xmlrpc_uripath;

$client = XML_RPC2_Client::create( $aria2_url );

$action = @$_REQUEST['action'];
$num = @min( $_REQUEST['num'], 200 );
$offset = @min( $_REQUEST['offset'], 201 );

if( strstr( $action, 'dialog_' )) {
	include( 'dialogs.php');
}

switch( $action ) {
	// Retrieves all files in the download queue
	case 'tellActive':
		$totalCount = 0;
		$items = array();
		try {
			$result = $client->aria2_tellActive('');
		}
		catch( XML_RPC2_FaultException $e ) {
			$msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ")aria2_tellStopped($offset, $num );";
			$success = false;
			sendResult($success, $msg);
			exit;
		}	
		catch(XML_RPC2_CurlException $e ) {
		}
		if( !empty($result)) {
			foreach( $result as $file ) {
				$totalCount ++;
				$items[]  = $file;
			}
		}
		try {
			$offset = intval($offset);
			$num = intval($num);
			$result = $client->aria2_tellWaiting($offset,$num);
		}
		catch( XML_RPC2_FaultException $e ) {
			$msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ")aria2_tellStopped($offset, $num );";
			$success = false;
			sendResult($success, $msg);
			exit;
		}	
		catch(XML_RPC2_CurlException $e ) {
		}
		if( !empty($result)) {
			foreach( $result as $file ) {
				$totalCount ++;
				$items[]  = $file;
			}
		}
		$n=0;
		foreach( $items as $item) {
			if( $item['completedLength'] != 0 ) {
				$completedPercentage = ' ('.round( ($item['completedLength'] / $item['totalLength'])*100 , 2) .'%)';
			} else {
				$completedPercentage = '(0%)';
			}
			$items[$n]['completedLength'] = parse_file_size($item['completedLength']).$completedPercentage;
			$items[$n]['totalLength'] = parse_file_size($item['totalLength']);
			$items[$n]['downloadSpeed'] = parse_file_size($item['downloadSpeed']).'/s';
			$items[$n]['uploadSpeed'] = parse_file_size($item['uploadSpeed']).'/s';
			$items[$n]['estimatedTime'] = calc_remaining_time( $item['downloadSpeed'], $item['totalLength']-$item['completedLength'] );
			try {
				$fresult = $client->aria2_getFiles( $items[$n]['gid'] );
			}
			catch (XML_RPC2_FaultException $e) {
				sendResult(false,  'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')' );
				//exit;
			}
			catch( XML_RPC2_CurlException $e ) {
				sendResult(false, 'Exception: ' . $e->getMessage() . ')' );
				//exit;
			}
			$ffile = $fresult[0]['path']; //TODO
			$items[$n]['bitfield'] = basename($ffile);
			$n ++;
		}
		
		$return = array( 'totalCount' => $totalCount,
									 'items' => $items );
		echo json_encode($return);
		die;
		
	case 'getFiles':		
		try {
			$result = $client->aria2_getFiles( strval(intval($_REQUEST['gid'])) );
			$return = array( 'gid' => intval($_REQUEST['gid']),
										'numFiles' => count( $result ),
										'totalLength' => parse_file_size($result[0]['length']),
										'items' => $result 
								); 
			echo json_encode($return);
			die;
			
		} 
		catch( XML_RPC2_FaultException $e ) {
			$msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ")";
			$success = false;	
			sendResult($success, $msg);
		}
		catch(XML_RPC2_CurlException $e ) {
			$msg = 'Exception: ' . $e->getMessage().' (ErrorCode '. $e->getCode() . ')<br />';
			sendResult(false,$msg);
		}
		exit;
			
	case 'tellStopped':
		$totalCount = 0;
		try {
			$offset = intval($offset);
			$num = intval($num);
			$result = $client->aria2_tellStopped( $offset, $num  );
			$stoppedArr =  $result[0];
			if( !empty($result)) {
				foreach( $result as $file ) {
						switch( $file['errorCode'] ) {
							case 0:$status = 'download successfully'; break;
							case 1:$status = 'unknown error occured'; break;
							case 2:$status = 'time out occured'; break;
							case 3:$status = 'resource not found'; break;
							case 4:$status = 'resource not found'; break;
							case 5:$status = 'download aborted, because download speed was too slow'; break;
							case 6:$status = 'network problem occured'; break;
						}
						if( $file['completedLength'] != 0 ) {
							$completedPercentage = ' ('.round( ($file['completedLength'] / $file['totalLength'])*100 , 2) .'%)';
						} else {
							$completedPercentage = '(0%)';
						}
						$file['status'] .= ', '. $status;
						$file['completedLength'] = parse_file_size($file['completedLength']).$completedPercentage;
						$file['totalLength'] = parse_file_size($file['totalLength']);
						try {
							$fresult = $client->aria2_getFiles( $file['gid'] );
						}
						catch (XML_RPC2_FaultException $e) {
							sendResult(false,  'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')' );
							//exit;
						}
						catch( XML_RPC2_CurlException $e ) {
							sendResult(false, 'Exception: ' . $e->getMessage() . ')' );
								//exit;
						}
						$ffile = $fresult[0]['path']; //TODO
						$file['bitfield'] = basename($ffile);
						$totalCount ++;
						$items[] = $file;
					}
				
			}
			$return = array( 'totalCount' => $totalCount,
							 'items' => $items );
			echo json_encode($return);
		} 
		catch( XML_RPC2_FaultException $e ) {
			$msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ")aria2_tellStopped($offset, $num );";
			$success = false;	
			sendResult($success, $msg);
		}
		exit;
	case 'tellStatus':
		//aria2.tellStatus gid
		//This method returns download progress of the download denoted by gid. gid is of type string. The response is of type struct and it contains following keys. The value type is string.
		
	case 'addUri':
		//aria2.addUri uris[, options[, position]]
		//This method adds new HTTP(S)/FTP/BitTorrent Magnet URI. uris is of type array and its element is URI which is of type string. For BitTorrent Magnet URI, uris must have only one element and it should be BitTorrent Magnet URI. options is of type struct and its members are a pair of option name and value. See Options below for more details. If position is given as an integer starting from 0, the new download is inserted at position in the waiting queue. If position is not given or position is larger than the size of the queue, it is appended at the end of the queue. This method returns GID of registered download.
		
		$msg = 'No URI provided';
		$success = true;
		$fconn = $_POST['userfileconn'];
		foreach($_POST['userfile'] as $url ) {
			if( !empty($url)) {
				try { 
					$result = $client->aria2_addUri( array($url), array('max-connection-per-server' => $fconn) );
					$msg = 'URI added';
				}
				catch (XML_RPC2_FaultException $e) {	
					$msg .= 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')<br />';
					$success = false;
				}
			}
		}
		sendResult($success, $msg);
		exit;
		
		
	case 'addTorrent':
		//aria2.addTorrent torrent[, uris[, options[, position]]]
		//This method adds BitTorrent download by uploading .torrent file. If you want to add BitTorrent Magnet URI, use aria2.addUri method instead. torrent is of type base64 which contains Base64-encoded .torrent file. uris is of type array and its element is URI which is of type string. uris is used for Web-seeding. For single file torrents, URI can be a complete URI pointing to the resource or if URI ends with /, name in torrent file is added. For multi-file torrents, name and path in torrent are added to form a URI for each file. options is of type struct and its members are a pair of option name and value. See Options below for more details. If position is given as an integer starting from 0, the new download is inserted at position in the waiting queue. If position is not given or position is larger than the size of the queue, it is appended at the end of the queue. This method returns GID of registered download.
	
	case 'addMetalink':
		//aria2.addMetalink metalink[, options[, position]]
		//This method adds Metalink download by uploading .metalink file. metalink is of type base64 which contains Base64-encoded .metalink file. options is of type struct and its members are a pair of option name and value. See Options below for more details. If position is given as an integer starting from 0, the new download is inserted at position in the waiting queue. If position is not given or position is larger than the size of the queue, it is appended at the end of the queue. This method returns array of GID of registered download.

	case 'remove':
		//aria2.remove gid
		//This method removes the download denoted by gid. gid is of type string. If specified download is in progress, it is stopped at first. The status of removed download becomes "removed". This method returns GID of removed download.
		if( !empty( $_POST['selitems'])) {
			
			$success = true;
			foreach($_POST['selitems'] as $gid ) {
				if( !empty($gid)) {
					try { 
						$result = $client->aria2_remove( $gid );
						$msg = 'File removed from queue';
					}
					catch (XML_RPC2_FaultException $e) {	
						$msg .= 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')<br />';
						$success = false;
					}
				}
			}
			sendResult($success, $msg);
			exit;
		}
	case 'getUris':
		//aria2.getUris gid
		//This method returns URIs used in the download denoted by gid. gid is of type string. The response is of type array and its element is of type struct and it contains following keys. The value type is string.
		
	case 'getFiles':
		//aria2.getFiles gid
		//This method returns file list of the download denoted by gid. gid is of type string. The response is of type array and its element is of type struct and it contains following keys. The value type is string.

	case 'getPeers':
		//aria2.getPeers gid
		//This method returns peer list of the download denoted by gid. gid is of type string. This method is for BitTorrent only. The response is of type array and its element is of type struct and it contains following keys. The value type is string.
		
	case 'changePosition':
		//aria2.changePosition gid, pos, how
		//This method changes the position of the download denoted by gid. pos is of type integer. how is of type string. If how is "POS_SET", it moves the download to a position relative to the beginning of the queue. If how is "POS_CUR", it moves the download to a position relative to the current position. If how is "POS_END", it moves the download to a position relative to the end of the queue. If the destination position is less than 0 or beyond the end of the queue, it moves the download to the beginning or the end of the queue respectively. The response is of type integer and it is the destination position.
		//For example, if GID#1 is placed in position 3, aria2.changePosition(1, -1, POS_CUR) will change its position to 2. Additional aria2.changePosition(1, 0, POS_SET) will change its position to 0(the beginning of the queue).

	case 'changeOption':
		//aria2.changeOption gid, options
		//This method changes options of the download denoted by gid dynamically. gid is of type string. options is of type struct and the available options are: bt-max-peers, bt-request-peer-speed-limit, max-download-limit and max-upload-limit. This method returns "OK" for success.
		try { 
			unset($_POST['action']);
			$gid = strval($_POST['gid']);
			unset($_POST['gid']);
			$result = $client->aria2_changeOption( $gid, $_POST );
			if( $result == 'OK' ) {
				$msg = 'File Options changed';
				$success = true;
			} else {
				$msg = 'Failed to change file options';
				$success = false;
			}
		}
		catch (XML_RPC2_FaultException $e) {	
			$msg = 'Exception: ' .$gid. $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')';
			$success = false;
		}
		sendResult($success, $msg);
		exit;
	case 'changeGlobalOption':
		//aria2.changeGlobalOption options
		//This method changes global options dynamically. options is of type struct and the available options are max-concurrent-downloads, max-overall-download-limit and max-overall-upload-limit. This method returns "OK" for success.
		try { 
			unset($_POST['action']);
			$result = $client->aria2_changeGlobalOption( $_POST );
			if( $result == 'OK' ) {
				$msg = 'Global Options changed';
				$success = true;
			} else {
				$msg = 'Failed to change global options';
				$success = false;
			}
		}
		catch (XML_RPC2_FaultException $e) {	
			$msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')';
			$success = false;
		}
		sendResult($success, $msg);
		exit;
	case 'purgeDownloadResult':
		//aria2.purgeDownloadResult
		//This method purges completed/error/removed downloads to free memory. This method returns "OK".
	case 'getVersion':
		//aria2.getVersion
		//This method returns version of the program and the list of enabled features. The response is of type struct and contains following keys.
		try { 
			$result = $client->aria2_getVersion();
			if( is_array($result ) ) {
				$result = json_encode($result);
				header("Content-type: text/html");
				echo $result;
				exit;
			} else {
				$msg = 'Failed to get Version';
				$success = false;
			}
		}
		catch (XML_RPC2_FaultException $e) {	
			$msg = 'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')';
			$success = false;
		}
		catch( XML_RPC2_CurlException $e ) {
			$msg = 'Exception: ' . $e->getMessage() . ')';
			$success = false;
		
		}
		sendResult($success, $msg);
		exit;
	case 'download':
		$gid = strval(intval($_REQUEST['gid']));
		try {
			$result = $client->aria2_getFiles( $gid );
		}
		catch (XML_RPC2_FaultException $e) {	
			sendResult(false,  'Exception: ' . $e->getFaultString().' (ErrorCode '. $e->getFaultCode() . ')' );
			exit;
		}
		catch( XML_RPC2_CurlException $e ) {
			sendResult(false, 'Exception: ' . $e->getMessage() . ')' );
			exit;
		}
		$file = $result[0]['path']; //TODO
		$filename = basename($file);
		if (!file_exists($file)) {
			sendResult( false, 'File not found');exit;
		}

		if (!is_readable($file)) {
			sendResult(  false, 'File not readable');exit;
		}

		@set_time_limit( 0 );

		$browser = id_browser();

		if ($browser=='IE' || $browser=='OPERA') {
			header('Content-Type: application/octetstream; Charset=UTF-8' );
		} else {
			header('Content-Type: application/octet-stream; Charset=UTF-8');
		}

		header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize(realpath($file)));
		//header("Content-Encoding: none");

		if($browser=='IE') {
			// http://support.microsoft.com/kb/436616/ja
			header('Content-Disposition: attachment; filename="'.urlencode($filename).'"');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		} else {
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
		}

 		@readFileChunked($file);
 		
		ob_end_flush();
		exit;
}

?>