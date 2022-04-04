<?php
namespace product_video;

class Product_Video {

    public function addProductVideo(string $video_id, string $video_url, array $data = [], int $product_id, string $image, int $import, int $status): array {
        try {
			return [
                "author_name"       => $data['author_name'],
                "author_url"        => $data['author_url'],
                "video_title"       => $data['title'],
				"video_url"       	=> $video_url,
                "thumbnail_url"     => $data['thumbnail_url'],
                "video_id"			=> $video_id,
				"product_id"		=> $product_id,
				"image"				=> $image,
				"import"			=> $import,
				"status"			=> $status
            ];
		} catch (Throwable $error) {
			$this->log->write($error->getMessage());
		}
    }

    public function get_youtube_video(string $video_id): array {
		try {
			$video_data = @file_get_contents('https://www.youtube.com/oembed?url=http://www.youtube.com/watch?v=' . $video_id . '&format=json'); //get JSON video details
			$response = json_decode($video_data, true); //parse the JSON into an array
			$http_code = $this->parseHeaders($http_response_header);
			return ['http_code'=>$http_code['reponse_code'], 'body'=>$response];
		} catch (Throwable $error) {
			$this->log->write($error->getMessage());
		}
	}

    public function parseHeaders(array $headers): array {
		try { 
			$head = array();
			foreach( $headers as $k=>$v ){
				$t = explode( ':', $v, 2 );
				if( isset( $t[1] ) ){
					$head[ trim($t[0]) ] = trim( $t[1] );
				} else {
					$head[] = $v;
					if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
						$head['reponse_code'] = intval($out[1]);
				}
			}
			return $head;
		} catch (Throwable $error) {
			$this->log->write($error->getMessage());
		}
	}

	public function remove_dir(string $dir): void {
		try {
			if ($objs = glob($dir . '/*')) {
				foreach($objs as $obj) {
					is_dir($obj) ? remove_dir($obj) : unlink($obj);
				}
			}
			rmdir($dir);
		} catch (Throwable $error) {
			$this->log->write($error->getMessage());
		}
	}

    public function save_image(string $img_url, string $path): void {
        try {
            $ch = curl_init ($img_url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
			$raw = curl_exec($ch);
			curl_close ($ch);
			if(file_exists($path)){
				unlink($path);
			}
			$fp = fopen($path,'x');
			fwrite($fp, $raw);
			fclose($fp);
        } catch (Throwable $error) {
            $this->log->write($error->getMessage());
        }
    }
}