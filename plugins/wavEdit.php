<?php
/*
/= Class      WavEdit v.1.20 (Wave File Editor)
/= Date:      09/27/2005
/= Author:    Thi Dinh (dht@pviet.com)
/=
/= This class includes some function to edit a pcm uncompressed wav file
/= It works on the simple 1 or 2 channel(8/16bits) wav file
/=
/= Functions in this version include:
/=   - View wav file header info
/=   - Draw Wav image
/=   - Join files
/=   - Adjust Volume
/=   - Fade in, Fade out
/=   - Crop Wav File
/=   - Echo
/=
/=
/= Change:
/=  - 1.20 - Add 2 channels(stereo)
/=  - 1.10 - Optimize Crop, Fade and Echo functions
/=
*/


Class wavEdit{
	var $afiles = array();    //array of files
	var $arrData = array();

	var $wavs = array();
	var $header = "";

	var $imgWidth;
	var $imgHeight;
	var $bgColor;
	var $grpColor;
	var $bdColor;
	var $gridColor;
	var $imgBorder;
	var	$visual_graph_mode;
	var $Db;

	function wavEdit(){
		//Clip value
		$this->peakClip8 = 250;
		$this->peakClip16 = 32000;

		//Image Config
		$this->font = 3;
		$this->fontaxis = 2;
		$this->numberGrid = 8;

		$this->imgWidth = 500;
		$this->imgHeight = 200;
		$this->grpColor = "#0000FF";
		$this->bgColor = "#DDF3DD";
		$this->gridColor = "#D0D0D0";
		$this->bdColor = "#CECEEC";
		$this->titleColor = "#0000FF";
		$this->imgBorder = 1;
		$this->visual_graph_mode = 0;

	}


//== Get File content ==//
	function getFiles($arr){
		if(is_array($arr)){
			for($i=0;$i<sizeof($arr);$i++){
				$this->afiles[$i]['name'] = $this->getFileName($arr[$i]);
				$content = @file_get_contents($arr[$i]) or die("HALT: Cannot retrieve file " . $arr[$i] . "! Please check again");
				$this->getStructure($content);
				$this->afiles[$i]['structure'] = $this->wavs;
				$this->getDataArr($this->unpackData($this->wavs['Data']));
				$this->afiles[$i]['arrdata']= $this->arrData;
			}
		}
		else{
			return false;
		}
	}

//== Get Wave file Name ==//
	function getFileName($path){
		if(strstr($path,'/') OR strstr($path,"\\")){
			$path = str_replace('\\','/', $path);
			$pattern = "/^(.*?)(\/.*?)+(\/.*?)(.wav)$/i";
			if(preg_match($pattern,$path,$matches)){
				return ltrim($matches[3],'/');
			}
			else{
				$name = "wavefile_" . $this->default_name++;
				return $name;
			}
		}
		else{
			$name = str_replace(".wav","",$path);
			return $name;
		}
	}


//== Get structure of wav file ==//
	function getStructure($binstr){
		$this->wavs['ChunkID'] = substr($binstr,0,4);
		$this->wavs['ChunkSize'] = $this->unpackHeader(substr($binstr,4,4));
		$this->wavs['Format'] = substr($binstr,8,4);
		$this->wavs['Subchunk1ID'] = substr($binstr,12,4);
		$this->wavs['SubChunk1Size'] = $this->unpackHeader(substr($binstr,16,4));
		$this->wavs['AudioFormat'] = $this->unpackHeader(substr($binstr,20,2));
		$this->wavs['NumChannels'] = $this->unpackHeader(substr($binstr,22,2));
		$this->wavs['SampleRate']  = $this->unpackHeader(substr($binstr,24,4));
		$this->wavs['ByteRate'] = $this->unpackHeader(substr($binstr,28,4));
		$this->wavs['BlockAlign'] = $this->unpackHeader(substr($binstr,32,2));
		$this->wavs['BitsPerSample'] = $this->unpackHeader(substr($binstr,34,2));

		//check extra format bytes
		if($this->wavs['SubChunk1Size']==16){
			$this->wavs['Subchunk2ID'] = substr($binstr,36,4);
			$this->wavs['SubChunk2Size'] = $this->unpackHeader(substr($binstr,40,4));
			$this->wavs['Data']= substr($binstr,44,strlen($binstr));
		}
		elseif($this->wavs['SubChunk1Size']==18){
			$this->wavs['ExtraFormatBytes'] = substr($binstr,36,2);
			$this->wavs['Subchunk2ID'] = substr($binstr,38,4);
			$this->wavs['SubChunk2Size'] = $this->unpackHeader(substr($binstr,42,4));
			$this->wavs['Data']= substr($binstr,46,strlen($binstr));
		}

		$this->wavs['NumberSamples']= ($this->wavs['SubChunk2Size']*8)/($this->wavs['NumChannels']*$this->wavs['BitsPerSample']);
	}


//== Get Wav Header ==//
	function getHeader(){
		$channels = $this->wavs['NumChannels'];
		$sampleRate = $this->wavs['SampleRate'];
		$bitSample = $this->wavs['BitsPerSample'];
		if($this->wavs['SubChunk1Size']==18){			//just don't want extra format!
			$this->wavs['SubChunk1Size']=16;
			$this->wavs['ChunkSize']-=2;
		}

		$header = "";
		$header .= pack( 'N*', 0x52494646);
		$header .= pack( 'V*', $this->wavs['ChunkSize']);
		$header .= pack( 'N*', 0x57415645);
		$header .= pack( 'N*', 0x666d7420);
		$header .= pack( 'V*', $this->wavs['SubChunk1Size']);
		$header .= pack( 'v*', $this->wavs['AudioFormat']);
		$header .= pack( 'v*', $channels);
		$header .= pack( 'V*', $sampleRate);
		$header .= pack( 'V*', $sampleRate * $channels * $bitSample/8);
		$header .= pack( 'v*', $this->wavs['BlockAlign']);
		$header .= pack( 'v*', $bitSample);
		$header .= pack( 'N*', 0x64617461 );
		$header .= pack( 'V*', $this->wavs['SubChunk2Size']);

  	return $header;
 }


//== Get Decimal Values ==//
	function unpackHeader($str,$opt=""){
		if(strlen($str)>2){
			$arr = unpack("V*",$str);
		}
		else{
			$arr = unpack("v*",$str);
		}
		return $arr[1];
	}

//== unpackData ==//
	function unpackData($strbin){
		if($this->wavs['BlockAlign']==1){
			return unpack("C*",$strbin);				//unsign Character
		}
		else{
			return unpack("s*",$strbin);				//sign int16
		}
	}

//== getArrayData ==//
	function getDataArr($arr){
		$this->arrData = array();
		if($this->wavs['NumChannels']==1){
			$this->arrData[0] = $arr;
		}
		else{
			$arrsize = sizeof($arr);
			for($j=1;$j<$arrsize;$j++){
				if($j%2==0){
					$this->arrData[0][] = $arr[$j];
				}
				else{
					$this->arrData[1][] = $arr[$j];
				}
			}
		}
	}

//== Chop Data from top or tail in milisecond ==//
	function cropFiles($arr){
		for($i=0;$i<sizeof($this->afiles);$i++){
			$this->wavs = $this->afiles[$i]['structure'];
			$position = $arr[$i]['pos'];
			$value = $arr[$i]['val'];

			$this->doCrop($position,$value);
			$this->afiles[$i]['structure'] = $this->wavs;
			$this->getDataArr($this->unpackData($this->wavs['Data']));
			$this->afiles[$i]['arrdata']= $this->arrData;
		}
	}

	function doCrop($pos,$time){
		$new_data = "";
		$bytes = ($time==0)? 0 : $this->getBytes($time);

		if($bytes<>false){
			$datasize =  strlen($this->wavs['Data']);
			$tail = $datasize - $bytes;

			if($pos=="head"){
				$new_data = substr($this->wavs['Data'],$bytes,$datasize);
			}
			elseif($pos=="tail"){
				$new_data = substr($this->wavs['Data'],0,$tail);
			}
		}
		else{
			$new_data = $this->wavs['Data'];
		}

		$this->wavs['NumberSamples'] -= $bytes/$this->wavs['BlockAlign'];
		$this->wavs['SubChunk2Size'] = $this->wavs['NumberSamples']*$this->wavs['BlockAlign'];
		$this->wavs['ChunkSize'] = $this->wavs['SubChunk2Size']+36;
		$this->wavs['Data'] = $new_data;
	}


//== Get data bytes in milisecond ==//
	function getBytes($val){
		$bytesPerSecond = $this->wavs['ByteRate'];
		$total_playtime = 1000*$this->wavs['NumberSamples']/$bytesPerSecond;	//milisecond
		if($val>($total_playtime*$this->wavs['NumChannels']*$this->wavs['BlockAlign'])){
			echo "Error: Cannot crop more than a wave file<br>";
			return false;
		}
		else{
			return floor($val*$bytesPerSecond/1000);
		}
	}


//== Function Set Fade ==//
	function setFade($arr){
		for($i=0;$i<sizeof($this->afiles);$i++){
			$this->wavs = $this->afiles[$i]['structure'];
			$position = $arr[$i]['pos'];
			$value = $arr[$i]['val'];
			$bytes = $this->getBytes($value);

			$this->doFade($position,$bytes);

			$this->afiles[$i]['structure'] = $this->wavs;
			$this->getDataArr($this->unpackData($this->wavs['Data']));
			$this->afiles[$i]['arrdata']= $this->arrData;
		}
	}

	function doFade($pos,$bytes){
		$new_data = "";
		$factor = 1/$bytes;

		if($bytes<>false){
			if($pos=="head"){
				$tmp = substr($this->wavs['Data'],0,$bytes);
				$arrData = $this->unpackData($tmp);
				$i=0;
				foreach($arrData as $value){
					$gain = $i*$factor;
					if($this->wavs['BlockAlign']==1){
						$value = ($value*$gain) - (128*($gain-1));
						$value = $this->doClip($value,0,$this->peakClip8);
					}
					else{
						$value = $value*$gain;
						$value = $this->doClip($value,-1*$this->peakClip16,$this->peakClip16);
					}

					$new_data .= ($this->wavs['BlockAlign']==1)? pack('C*',$value) :  pack('s*',$value);
					$i++;
				}
				$new_data .= substr($this->wavs['Data'],$bytes,strlen($this->wavs['Data']));
			}

			elseif($pos=="tail"){
				$tail = strlen($this->wavs['Data']) - $bytes;
				$new_data .= substr($this->wavs['Data'],0,$tail);

				$tmp = substr($this->wavs['Data'],$tail,strlen($this->wavs['Data']));
				$arrData = $this->unpackData($tmp);
				$arrSize = sizeof($arrData);
				$i=0;
				foreach($arrData as $value){
					$gain = ($arrSize - $i)*$factor;
					if($this->wavs['BlockAlign']==1){
						$value = ($value*$gain) - (128*($gain-1));
						$value = $this->doClip($value,0,$this->peakClip8);
					}
					else{
						$value = $value*$gain;
						$value = $this->doClip($value,-1*$this->peakClip16,$this->peakClip16);
					}
					$new_data .= ($this->wavs['BlockAlign']==1)? pack('C*',$value) :  pack('s*',$value);
					$i++;
				}
			}

			$this->wavs['Data'] = $new_data;
		}
	}


//== Set Echo ==//
	function setEcho($arr){
		for($i=0;$i<sizeof($this->afiles);$i++){
			$this->wavs = $this->afiles[$i]['structure'];
			$this->arrData = $this->afiles[$i]['arrdata'];
			$position = $arr[$i]['pos'];
			$echo = $arr[$i]['echo'];
			$intensity = $arr[$i]['intensity'];

			if($position == "all"){
				$bytes = $this->wavs['NumberSamples'];
			}
			else{
				$bytes = $this->getBytes($position);
			}

			$this->doEcho($bytes,$echo,$intensity);

			$this->afiles[$i]['structure'] = $this->wavs;
			$this->getDataArr($this->unpackData($this->wavs['Data']));
			$this->afiles[$i]['arrdata']= $this->arrData;
		}
	}

	function doEcho($bytes,$echo,$inte){
		$new_data = "";
		$arr=$this->arrData[0];
		$samples = sizeof($arr);

		$tail = strlen($this->wavs['Data']) - $bytes;
		$tmp = substr($this->wavs['Data'],$tail,strlen($this->wavs['Data']));
		$arrData = $this->unpackData($tmp);
		$arrSize = sizeof($arrData);

		for($i=1;$i<=$echo;$i++){
			$factor = 1/($i*$bytes/$inte);
			$j=0;
			foreach($arrData as $value){
				$gain = ($arrSize - $j)*$factor;
				if($this->wavs['BlockAlign']==1){
					$value = ($value*$gain) - (128*($gain-1));
					$value = $this->doClip($value,0,$this->peakClip8);
				}
				else{
					$value = $value*$gain;
					$value = $this->doClip($value,-1*$this->peakClip16,$this->peakClip16);
				}
				$new_data .= ($this->wavs['BlockAlign']==1)? pack('C*',$value) :  pack('s*',$value);
				$j++;
			}

			$samples += $bytes/($this->wavs['BlockAlign']);
		}

		$this->wavs['Data'] .= $new_data;
		$this->wavs['NumberSamples'] = $samples;
		$this->wavs['SubChunk2Size'] = $this->wavs['NumberSamples'] * $this->wavs['BlockAlign'];
		$this->wavs['ChunkSize'] = $this->wavs['SubChunk2Size']+36;
	}


//== Set Volume ==//
	function setVolume($val,$fname=""){
		for($i=0;$i<sizeof($this->afiles);$i++){
			$this->wavs = $this->afiles[$i]['structure'];

			if($this->afiles[$i]['structure']['BlockAlign']==1){
				$this->doSetVol8Bit($val);
			}
			else{
				$this->doSetVol16Bit($val);
			}

			$this->afiles[$i]['structure'] = $this->wavs;
			$this->afiles[$i]['arrdata'] = array();
			$this->getDataArr($this->unpackData($this->wavs['Data']));
			$this->afiles[$i]['arrdata']= $this->arrData;
		}
	}

	function doSetVol8Bit($val){
		$new_data = "";
		$arr = $this->unpackData($this->wavs['Data']);
		foreach($arr as $num){
			$data = ($num*$val) - (128*($val-1));
			$data = $this->doClip($data,0,$this->peakClip8);
			$new_data .= pack('C*',$data);
		}
		$this->wavs['Data'] = $new_data;
	}

	function doSetVol16Bit($val){
		$str = "";
		$arr = $this->unpackData($this->wavs['Data']);
		foreach($arr as $num){
			$data = $num*$val;
			//$data = $this->doClip($data,-32768,32768);
			$data = $this->doClip($data,-1*$this->peakClip16,$this->peakClip16);
			$str .= pack('s*', $data);
		}
		$this->wavs['Data'] = $str;
	}


	function doClip($val,$min,$max){
		if($val<$min){
			$val=$min;
		}
		elseif($val>$max){
			$val = $max;
		}
		return $val;
	}

//== Join Wave Files ==//
	function joinFiles($fname=""){
		for($i=1;$i<sizeof($this->afiles);$i++){
			if(($this->afiles[$i]['structure']['NumChannels'] <> $this->afiles[$i-1]['structure']['NumChannels']) OR ($this->afiles[$i]['structure']['BlockAlign'] <> $this->afiles[$i-1]['structure']['BlockAlign'])){
				die("Error: You cannot join different type of wav file!");
				return false;
			}
		}

		$this->doJoin();			//process join files

		$this->afiles = array();
		$this->afiles[0]['name'] = (empty($fname))? "file_join": $fname;
		$this->afiles[0]['structure'] = $this->wavs;		//set file structure
		$this->getDataArr($this->unpackData($this->wavs['Data']));
		$this->afiles[0]['arrdata']= $this->arrData;
	}

	function doJoin(){
		$totalNumberSamples = 0;
		$strData = "";
		for($i=0;$i<sizeof($this->afiles);$i++){
			$totalNumberSamples += $this->afiles[$i]['structure']['NumberSamples'];
			$strData .= $this->afiles[$i]['structure']['Data'];
		}

		$this->wavs['NumberSamples'] = $totalNumberSamples;
		$this->wavs['SubChunk2Size'] = $totalNumberSamples * $this->wavs['BlockAlign'];
		$this->wavs['ChunkSize'] = $this->wavs['SubChunk2Size']+36;
		$this->wavs['Data'] = $strData;
		//$this->jfile = $this->getHeader() . $strData;

	}


//=============== Draw Wave Image===============//
// This function was modified from a part of AudioFile class
// which wrote by michael kamleither(mika@ssw.co.at)
// Check this class at: http://www.entropy.at/
//=============================================//

	function drawImgWave($val=""){
		for($i=0;$i<sizeof($this->afiles);$i++){
			if(empty($val)){
				$fname = $this->afiles[$i]['name'];
			}
			else{
				$fname = $this->afiles[$i]['name'] . "_" . $val;
			}
			$this->wavs = $this->afiles[$i]['structure'];

			$this->arrData = $this->afiles[$i]['arrdata'];
			$this->doDrawImg($fname);
		}
	}

	function doDrawImg($output){
		$arr = $this->arrData;
		$bytes_per_pixel = floor($this->wavs['NumberSamples']/$this->imgWidth);
		$visualData = array();

		for($c=0;$c<$this->wavs['NumChannels'];$c++){
			$dataindex = 1;
			$currentindex= 1;
			while($currentindex<sizeof($arr[0])){
				//$loopindex= 0;
				$visualData[$c][$dataindex] = ($this->wavs['BlockAlign']==1)? $arr[$c][$currentindex]:($arr[$c][$currentindex]+32768);

				$currentindex+= $bytes_per_pixel;
				$dataindex++;
			}
		}

		$imgHeight = $this->imgHeight*$this->wavs['NumChannels'];
		$im = @ImageCreate ($this->imgWidth, $imgHeight+20) or die ("Cannot Initialize new GD image stream!");

		$background_color = ImageColorAllocate ($im, hexdec(substr($this->bgColor,1,2)),hexdec(substr($this->bgColor,3,2)),hexdec(substr($this->bgColor,5,2)));
		$bground = ImageColorAllocate ($im, hexdec(substr($this->bgColor,1,2)),hexdec(substr($this->bgColor,3,2)),hexdec(substr($this->bgColor,5,2)));
		$graph = ImageColorAllocate ($im, hexdec(substr($this->grpColor,1,2)),hexdec(substr($this->grpColor,3,2)),hexdec(substr($this->grpColor,5,2)));
		$border = ImageColorAllocate ($im, hexdec(substr($this->bdColor,1,2)),hexdec(substr($this->bdColor,3,2)),hexdec(substr($this->bdColor,5,2)));
		$grid = ImageColorAllocate ($im, hexdec(substr($this->gridColor,1,2)),hexdec(substr($this->gridColor,3,2)),hexdec(substr($this->gridColor,5,2)));
		$titlecolor = ImageColorAllocate ($im, hexdec(substr($this->titleColor,1,2)),hexdec(substr($this->titleColor,3,2)),hexdec(substr($this->titleColor,5,2)));

		if($this->imgBorder){
			//Main image border
			ImageRectangle ($im,0,0,($this->imgWidth-1),($imgHeight-1+20),$border);
			//X-axis legend
			ImageLine ($im,0,($imgHeight-1),($this->imgWidth-1),($imgHeight-1),$border);
		}

		//Draw Text
		$title = $output . ".wav";
		$x = ($this->imgWidth - floor(strlen($title))*imagefontwidth($this->font) )/2;
		$y = 2;
		imagestring($im,$this->font,$x,$y,$title,$titlecolor);

		//Draw Grid
		$numbergrid = $this->numberGrid;
		$pixel_per_grid = sizeof($visualData[0])/$numbergrid;
		for($i=1;$i<$numbergrid;$i++){
			$gx = $pixel_per_grid*$i;
			ImageLine($im,$gx,0,$gx,$imgHeight,$grid);

			//draw axis legend
			$xaxis = $gx*$bytes_per_pixel;
			if($xaxis<sizeof($this->arrData[0])){
				$xtime = floor($xaxis*1000/$this->wavs['SampleRate']);
				$xtext = $xtime . "ms";
				if($xtime>1000){
					$xtext = round($xtext/1000,2) . "s";
				}
			}
			$xpos = $gx - floor(strlen($xtime))*imagefontwidth($this->fontaxis)/2;
			imagestring($im,$this->fontaxis,$xpos,($imgHeight+5),$xtext,$titlecolor);
		}

		// this for-loop draws a graph for every channel
		$middle = ($this->wavs['BlockAlign']==1)? 128 : 32768;
		$height_channel = $imgHeight/$this->wavs['NumChannels'];
		for($c=0;$c<$this->wavs['NumChannels'];$c++){
			$factor = $imgHeight/($middle*2*$this->wavs['NumChannels']);
			$last_x = 1;
			$last_y = (2*$c+1)*($imgHeight)/(2*$this->wavs['NumChannels']);

			for($i=1;$i<$this->imgWidth;$i++){
				$val = ($visualData[$c][$i]*$factor)+($c*$height_channel);

				//echo "$c - $i: " . $visualData[$c][$i] . " - $factor - $val<br>\n";
				if($this->visual_graph_mode == 0){
					ImageLine ($im,$last_x,$last_y,$i,$val,$graph);
				}
				else{
					ImageLine ($im,$i,($height_channel),$i,$val,$graph);
				}
				$last_x = $i;
				$last_y = $val;
			}
		}


		$imgName = $output . ".png";
		imagepng($im,$imgName);
		imagedestroy($im);

	}

//== View Wav Header ==//
	function viewImage($name="",$opt=""){
		for($i=0;$i<sizeof($this->afiles);$i++){
			$fname = (empty($name)) ? $this->afiles[$i]['name'] . ".png" : $this->afiles[$i]['name'] . "_{$name}.png";
			$sname = (empty($name)) ? $this->afiles[$i]['name'] : $this->afiles[$i]['name'] . "_" . $name;

			echo "<table width='100%' cellspacing='4' cellpadding='4'>\n";
			echo "  <tr>\n";
			echo "    <td width='20%' align='right' valign='top'>\n";
			if($opt<>"nofile"){
				echo "     <a href='listen.php?f=$sname'>{$sname}.wav</a><br><br>\n";
			}

			if($opt=="viewheader"){
				$this->viewHeader($this->afiles[$i]['name']);
			}

			echo "    </td>\n";
			echo "    <td width='70%' align='left' valign='top'>\n";
			echo "      <img src='$fname' border='0'>\n";
			echo "     </td>\n";
			echo "  </tr>\n";
			echo "</table>\n";
		}
	}


//== Write File ==//
	function writeWavFile($val=""){
		for($i=0;$i<sizeof($this->afiles);$i++){
//			$fname = (empty($val)) ? $this->afiles[$i]['name'] . ".wav" : $this->afiles[$i]['name'] . "_" . $val . ".wav";
			$fname = (empty($val)) ? $this->afiles[$i]['name'] . ".wav" : $val . ".wav";
			$this->wavs = $this->afiles[$i]['structure'];
			$newContent = $this->getHeader() . $this->wavs['Data'];

			$fd = fopen($fname,"w");
			fwrite($fd,$newContent);
			fclose($fd);
		}
	}



//== View Wav Header ==//
	function viewHeader($fname=""){
		if(!empty($fname)){
			foreach($this->afiles AS $arr){
				if($arr['name']==$fname){
					$head = $arr['structure'];
					break;
				}
			}
			$this->doViewHeader($head);
		}
		else{
			foreach($this->afiles AS $arr){
				$head = $arr['structure'];
				$this->doViewHeader($head);
			}
		}
	}

	function doViewHeader($head){
		$str = "<b>Header Info.</b><br><br>";
		foreach($head as $key=>$val){
			if($key<>"Data"){
				$str .= "$key: $val<br>";
			}
		}
		$str .= "Playing time: " . round($head['NumberSamples']/$head['SampleRate'],2) . " s";
		echo $str;
	}



/* End Class */
}

?>