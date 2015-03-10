<?php 

class LastFM
{
 
	private $api = "LAST_FM_API_ID_GOES_HERE";
	private $secret = "LAST_FM_SECRET_ID_GOES_HERE";
	private $username = "LAST_FM_USERNAME_GOES_HERE";
	private $artist;
	private $artistData = array();
	private $topArtists = array();
	public $artist_bio_details = array();
	private $count;
 
 
	public function __construct($count=1) 
	{
		$this->count = $count;
	}
 
	private static function fetchUrl($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$retData = curl_exec($ch);
		curl_close($ch);
 
		return $retData;
	}	
 
	private static function time_ago($date, $granularity=1) 
	{		
		$retval = '';
		$difference = time() - $date;
		$periods = array('
				decade' => 315360000,
				'year' => 31536000,
				'month' => 2628000,
				'week' => 604800, 
				'day' => 86400,
				'hour' => 3600,
				'minute' => 60,
				'second' => 1
				);
 
		foreach ($periods as $key => $value) 
		{
			if ($difference >= $value) 
			{
				$time = floor($difference/$value);
				$difference %= $value;
				$retval .= ($retval ? ' ' : '').$time.' ';
				$retval .= (($time > 1) ? $key.'s' : $key);
				$granularity--;
			}
			if ($granularity == '0') { break; }
		}
 
		return ' about '.$retval.' ago';      
	}
 
	public function topTracks()
	{
		$topfeeds = "http://ws.audioscrobbler.com/2.0/?method=user.gettopartists&user=".$this->username."&period=overall&limit=".$this->count."&format=json&api_key=".$this->api."";
		$result = self::fetchUrl($topfeeds);
		$json = json_decode($result);
 
		for ($i=0; $i<$this->count; $i++) {
			$this->topArtists[$i]['name'] = $json->topartists->artist[$i]->name;
			$this->topArtists[$i]['playcount'] = $json->topartists->artist[$i]->playcount;
			$this->topArtists[$i]['url'] = $json->topartists->artist[$i]->url;
		}
	}
 
	public function loadTrackFeed()
	{
 
		$last_track = "ws.audioscrobbler.com/2.0/user/".$this->username."/recenttracks.rss";
		$rss = self::fetchUrl($last_track);
		$rss = simplexml_load_string($rss);
 
		for($i=0; $i<$this->count; $i++) {
			$this->artist_bio_details[$i]['url'] = $rss->channel->item[$i]->link;
			$this->artist_bio_details[$i]['title'] = $rss->channel->item[$i]->title;
			$this->artistData = explode(" – ", $this->artist_bio_details[$i]['title']);
			$this->artist_bio_details[$i]['artist'] = $this->artistData[0];
			$this->artist_bio_details[$i]['song'] = $this->artistData[1];
			$this->artist_bio_details[$i]['pubDate'] = $rss->channel->item[$i]->pubDate;
			$this->artist_bio_details[$i]['pubDate_time'] = strtotime($this->artist_bio_details[$i]['pubDate']);
			$this->artist_bio_details[$i]['time_ago'] = self::time_ago($this->artist_bio_details[$i]['pubDate_time']);
			//format is Artist - Track
			$this->artist = str_replace(" ", "+", $this->artistData[0]);
			$this->loadArtistBio($this->artist, $i);
		}
	}
 
	private function loadArtistBio($artist, $i) 
	{
		$artist_bio = "http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&artist=".$artist."&format=json&api_key=".$this->api."";
		//get track album cover and info	
		$result = self::fetchUrl($artist_bio);
		$json = json_decode($result);
		$this->artist_bio_details[$i]['image'] = $json->artist->image[2]->{'#text'};
		$this->artist_bio_details[$i]['bio'] = $json->artist->bio->summary;
	}
 
	public function showArtistInfo() 
	{
		return $this->artist_bio_details;
	}
 
	public function showTopTracks() 
	{
		return $this->topArtists;
	}
}
