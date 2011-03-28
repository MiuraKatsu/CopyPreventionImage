<?php

class CopyPreventionImage {

	var $__isSoftbank = false;
	var $__isDocomo   = false;
	var $__isKddi     = false;

	function __construct(){
	}	
	
	function rawdata($image_data,$content_type){
		header("Content-Type: ".$content_type);
		echo $Image_data;
		exit;
	}
	
	function image($image_data,$content_type){
		//画像を出力
		header("Content-Type: ".$content_type);
		//転送防止
		if($this->__isSoftbank){
			header("x-jphone-copyright: no-store, no-transfer, no-peripheral");
		}
		
		//転送防止(jpeg,gif)
		if($content_type == 'image/jpeg'){
			$image_data = $this->_jpegNoCopy($image_data);
			echo $image_data;
			
		}elseif($content_type == 'image/gif'){
			$image_data = $this->_gifNoCopy($image_data);
			echo $image_data;
		}elseif($content_type == 'image/x-png'){
			$image_data = $this->_pngNoCopy($image_data);
			echo $image_data;
		}else{
			echo $image_data;
		}
		exit;
	}

	function _jpegNoCopy($image_data){
		
		// バイナリのコメント部以外を抽出
		$part1 = explode("\xFF\xFE", $image_data, 2);
		if(isset($part1[1])){
			$part2 = explode("\xFF", $part1[1], 2);
			if ($this->__isDocomo) {
				// 000Bは「copy="NO"」の文字列バイト数(9) + 2 = 13 の16進数
				$image_data = $part1[0] . "\xFF\xFE\x00\x0Bcopy=\"NO\"\xFF" . $part2[1];
			} elseif ($this->__isKddi) {
				// 0013は「kddi_copyright=on」の文字列バイト数(17) + 2 = 19 の16進数
				$image_data = $part1[0] . "\xFF\xFE\x00\x13kddi_copyright=on\xFF" . $part2[1];
			}
		// コメント部がなかったらSOIの直後に追加
		}else{
			//JPEG SOI SEGMENT "\xFF\xD8"
			$p1 = explode("\xFF\xD8\xFF", $image_data, 2);
			if ($this->__isDocomo) {
				$image_data = "\xFF\xD8\xFF\xFE\x00\x0Bcopy=\"NO\"\xFF" . $p1[1];
			} elseif ($this->__isKddi) {
				$image_data = "\xFF\xD8\xFF\xFE\x00\x13kddi_copyright=on\xFF" . $p1[1];
			}
		}
		
		return $image_data;
	}
	
	function _gifNoCopy($image_data){
		// バイナリのコメント部以外を抽出
		$part1 = explode("\x21\xFE", $image_data, 2);
		if(isset($part1[1])){
			$part2 = explode("\x00", $part1[1], 2);
			if ($this->__isDocomo) {
				// 000Bは「copy="NO"」の文字列バイト数(9) + 2 = 13 の16進数
				$image_data = $part1[0] . "\x21\xFE\x09copy=\"NO\"\x00" . $part2[1];
			} elseif ($this->__isKddi) {
				// 0013は「kddi_copyright=on」の文字列バイト数(17) + 2 = 19 の16進数
				$image_data = $part1[0] . "\x21\xFE\x13kddi_copyright=on\x00" . $part2[1];
			}
		}else{
			$p1 = rtrim($image_data,"\x3B" );
			
			if ($this->__isDocomo) {
				$image_data = $p1 . "\x21\xFE\x09copy=\"NO\"\x00\x3B";
			} elseif ($this->__isKddi) {
				$image_data = $p1 . "\x21\xFE\x11kddi_copyright=on\x00\x3B";
			}
		}
		return $image_data;
	}
	
	function _pngNoCopy($image_data){
		//tEXtチャンク
		//74 45 58 74
		$tEXt = "\x74\x45\x58\x74";
		//null(\x00)
		$null = "\x00";
		//キーワード(Copyright)
		//データサイズ 00 00 00 1B
		//テキストkddi_copyright=on/copy=\"NO\"
		if($this->__isDocomo){
			$keyword = "Comment";
			$size = "\x00\x00\x00\x11";
			$text = "copy=\"NO\"";
		}elseif($this->__isKddi){
		
			$keyword = "Copyright";
			$size = "\x00\x00\x00\x1B";
			$text = "kddi_copyright=on";
		}
		//CRC
		$crc = pack('L',sprintf("%u",crc32($tEXt . $keyword . $null .$text)));
		
		$part1 = substr($image_data,0,33);
		$part2 = substr($image_data,33);
		
		$image_data = $part1 . $size . $tEXt . $keyword . $null . $text . $crc . $part2;
		
		return $image_data;
	}
	
}

