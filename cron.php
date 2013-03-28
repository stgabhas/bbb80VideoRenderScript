<?php

$BBB_DEFAULT_DIR = "/var/bigbluebutton/published";
$BBB_PUBLISHED_DIR = "/var/bigbluebutton/published/slides/";
$command = "ls $BBB_PUBLISHED_DIR"; 
exec($command, $meetingList);
foreach($meetingList as $meeting)
{
    //Final result will be lib264 format of video file named as slides.mp4
    if(!file_exists($BBB_PUBLISHED_DIR.$meeting."/slides.mpg"))
    {
        extract_info_from_xml($BBB_PUBLISHED_DIR.$meeting);
        concat_all_mpg($BBB_PUBLISHED_DIR.$meeting);
        join_audio_video($BBB_PUBLISHED_DIR.$meeting);
    }
    //If slides.mp4 exists, do nothing
    else
    {
        //do nothing    
    }
}


function extract_info_from_xml($xmlfile_location)
{
    $reader = new XMLReader();
    $xmlfile = $xmlfile_location."/slides.xml";
    if(!$reader->open($xmlfile))
    {
        die("Failed to open $xmlfile");
    }
    
    $i = 1; //index
    //Each slide is stored in an array
    $slideArr = array();
    
    while($reader->read())
    {
        if($reader->name == 'image')
        {
            $in = $reader->getAttribute('in');
            $out = $reader->getAttribute('out');
            $src = $reader->getAttribute('src');
            $slide_number = $i;
            $slideArr[$i++] = array('in'=>$in, 'out'=>$out, 'src'=>$src, 'slide_number'=>$slide_number);
        }
    }
    
    $reader->close();
    foreach($slideArr as $item)
    {
        img2mpg($item['in'], $item['out'], $item['src'], $item['slide_number'], $xmlfile_location);
    }
}

function img2mpg($in, $out, $src, $slide_number, $xmlfile_location)
{
    $BBB_DEFAULT_DIR = "/var/bigbluebutton/published";
    $BBB_LOGO_SRC = "/var/bigbluebutton/playback/slides/logo.png";
    $time = $out - $in;
    if($src=='logo.png')
    {
        $command = "ffmpeg -loop 1 -f image2 -i $BBB_LOGO_SRC -t $time -q:v 3 $xmlfile_location/$slide_number.mpg";
    }
    else
    {
	   $command = "ffmpeg -loop 1 -f image2 -i $BBB_DEFAULT_DIR$src -t $time -q:v 3 $xmlfile_location/$slide_number.mpg";
    }
    system($command);
}

function concat_all_mpg($xmlfile_location)
{
    $BBB_DEFAULT_DIR = "/var/bigbluebutton/published";
    $getSlides = "ls $xmlfile_location/*.mpg";
    exec($getSlides, $list_of_files);
    natsort($list_of_files);
    
    $inputs = "";
    foreach($list_of_files as $file)
    {
      $inputs .= $file." ";
    }
    $command = "cat $inputs > $xmlfile_location/slides.mpg";
    system($command);
}

function join_audio_video($xmlfile_location)
{
    $BBB_DEFAULT_DIR = "/var/bigbluebutton/published";
    $AUDIO_SRC = $xmlfile_location."/audio/recording.wav";
    $VIDEO_SRC = $xmlfile_location."/slides.mpg";
    $command = "ffmpeg -i $AUDIO_SRC -i $VIDEO_SRC -vcodec copy $xmlfile_location/slides2.mpg";
    system($command);    
}

?>
