<?
/*Explorer*/

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;

/* App Info */
$AppContainer->AppNameInfo = 'Explorer';
$AppContainer->SecondNameInfo = 'Проводник';
$AppContainer->VersionInfo = '1.0.7';
$AppContainer->AuthorInfo = 'Forest Media';

/* Library List */
$AppContainer->LibraryArray = Array('filesystem','bd','gui');

/* Container Info */
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '530px';
$AppContainer->customStyle = 'padding-top:0px; max-width:100%;';
$AppContainer->isMobile = $_GET['mobile'];
$AppContainer->StartContainer();

//set var
$fo = new filecalc;
$faction = new fileaction;
$dialogexplorer = new gui;
$hashImage = md5($AppContainer->VersionInfo);

function convert($string){
	$string = preg_replace('#%u([0-9A-F]{4})#se','iconv("UTF-16BE","UTF-8",pack("H4","$1"))',$string);
	return $string;
}

$dir = convert($_GET['defaultloader']);
if(empty($dir)){
	$dir = convert($_GET['dir']);
}

$del = $_GET['del'];
$deleteforever = $_GET['delf'];
$link	=	$_GET['linkdir'];
$linkname	=	$_GET['linkname'];
$ico	=	$_GET['ico'];
$isMobile	=	$_GET['mobile'];
$Folder	=	$_GET['destination'];
$erasestatus	=	$_GET['erasestatus'];
$zipfile = $_GET['zipfile'];
$zipFileUnpack = $_GET['zipfileunpack'];

//load lang
$cl = $_SESSION['locale'];
$explorer_lang  = parse_ini_file('assets/lang/'.$cl.'.lang');

//delete
if($erasestatus){
	$faction->deleteDir($dir);
	mkdir($dir);
}

//make file
if (isset($_GET['makefile'])){
	$newFile = convert(str_replace(' ', '_', $_GET['makefile']));
	if(!is_file($dir.'/'.$newFile))
	{
		$defaultExt = '';
		preg_match('/\.[^\.]+$/i',$newFile,$ext);
		if($ext[0] == ''){
			$defaultExt = '.txt';
		}
		file_put_contents($dir.'/'.$newFile.$defaultExt,'');
	}else{
		$dialogexplorer->newnotification($AppName,$AppName,$explorer_lang['mfile_msg_1']);
	}
}

// make new dir
if (isset($_GET['makedir'])){
	if(!is_dir($dir.'/'.$_GET['makedir']))
	{
		if(!mkdir($dir.'/'.convert(str_replace(' ', '_', $_GET['makedir'])),0755)){
			$dialogexplorer->newnotification($AppName, $AppName, $explorer_lang['msg_1']." ".$_GET['makedir'].$explorer_lang['msg_2']);
		}
	}else{
		$dialogexplorer->newnotification($AppName, $AppName, $explorer_lang['msg_3']);
	}
}

//обрабатываем кнопки удаления и перемещения в корзину
if(!empty($del)){
	$faction->rmdir_recursive($del);
}
if(!empty($deleteforever)){
	if(is_dir($deleteforever)){
		$faction->deleteDir($deleteforever);
	}
	if(is_file($deleteforever)){
		if(!preg_match('/os.php/',$deleteforever) && !preg_match('/login.php/',$deleteforever) && !preg_match('/makeprocess.php/',$deleteforever)){
			unlink($deleteforever);
		}else{
			throw new InvalidArgumentException("can't delete system file: $dirPath");
		}
	}
}

/*-add to archive-*/
if(!empty($zipfile)){
	require '../../core/library/zip.php';
	if(is_dir($zipfile)){
		$zip = new zip;
		$zip->toZip($zipfile, dirname($zipfile).'/'.basename($zipfile).'.zip');
	}else{
		$zip = new ZipArchive;
		$info = pathinfo($zipfile);
		$zip->open(dirname($zipfile).'/'.basename($zipfile,'.'.$info['extension']).'.zip', ZIPARCHIVE::CREATE);
		$zip->addFile($zipfile, basename($zipfile));
		$zip->close();
	}
}

/*-extract archive-*/
if(!empty($zipFileUnpack)){
	$zip = new ZipArchive;
	if($zip->open($zipFileUnpack) == TRUE){
		$zip->extractTo(dirname($zipFileUnpack));
		$zip->close();
	}
}

/*- Link Create -*/
if(!empty($link)){
	$ico = stristr($ico,'?',true);
	if($linkname == 'main.php'){
		$mainfile	=	str_replace('.php','',$linkname);
		$destination = str_replace($linkname,'',$link);
		$link = '';
		$param = '';
		$file = stristr($destination, 'apps/');
		$file = str_replace(array('apps/','/main.php', '/'),'',$file);
  	$info = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/system/apps/'.$file.'/main.php?getinfo=true&h='.md5(date('dmyhis')));
		$arrayInfo = json_decode($info);
		if($_SESSION['locale'] == 'en'){
			$newname	=	$arrayInfo->{'name'};
			$puplicname	=	$arrayInfo->{'name'};
		}else{
			$newname	=	$arrayInfo->{'secondname'};
			$puplicname	=	$arrayInfo->{'secondname'};
		}
		if(empty($newname) || empty($puplicname)){
			$newname = $file;
			$puplicname = $file;
		}
	}else{
		if(is_file($link)){
			$ext =	pathinfo($link);
			$ext = mb_strtolower($ext['extension']);
			if($ext == 'php'){
				$mainfile	=	str_replace('.php','',$linkname);
				$newname = $linkname;
				$puplicname = $linkname;
				$destination = str_replace($linkname,'',$link);
			}else{
				$ini_array = parse_ini_file("../../core/extconfiguration.foc");
				$param = $ini_array[$ext.'_key'];
				$mainfile	=	'main';
				$destination = str_replace('main.php','',$ini_array[$ext]);
				$link = str_replace($_SERVER['DOCUMENT_ROOT'],'',$link);
				$puplicname = $linkname;
				$newname = str_replace(array('system','apps','/'),'',$destination);
			}
		}else{
			$mainfile	=	'main';
			$param = 'dir';
			$destination = "system/apps/Explorer/";
			$puplicname = $linkname;
			$newname = 'Explorer';
		}
	}

	$file = '../../users/'.$_SESSION["loginuser"].'/desktop/'.$puplicname.'_'.uniqid().'.link';
	$faction->makelink($file,$destination,$mainfile,$param,$link,$newname,$puplicname,$ico);
}

if (empty($dir)){
	$dir = '../../../';
}

if(!is_dir($dir)){
	$ext = pathinfo($dir);
	$ext = mb_strtolower($ext['extension']);
	if($ext == 'php'){
		$file = basename($dir,'.php');
		$dest = $dir;
		$dir = dirname($dir);
		$param = '';
		$keys = '';
	}else{
		$ini_array = parse_ini_file("../../core/extconfiguration.foc");
		$dest = $ini_array[$ext];
		$param	= str_replace($_SERVER['DOCUMENT_ROOT'],'',$dir);
		$keys = $ini_array[$ext.'_key'];
		$dir = dirname($dir);
	}

	if (!empty($dest)){

		$_dest = str_replace($_SERVER['DOCUMENT_ROOT'], '', $dest);

		$info = 'http://'.$_SERVER['HTTP_HOST'].file_get_contents($_dest.'?getinfo=true&h='.md5(date('dmyhis')));

	  $arrayInfo = json_decode($info);
	  if($_SESSION['locale'] == 'en'){
	    $name_launch = $arrayInfo->{'name'};
	  }else{
	    $name_launch = $arrayInfo->{'secondname'};
	  }

		$name_launch = str_replace(' ', '_', $name_launch);

		if(empty($name_launch)){
			$name_launch = 'Unknow_App';
		}

		?>
		<div id="makeprocess">
			<script>makeprocess('<?echo $dest?>','<?echo $param;?>','<?echo $keys;?>','<?echo $name_launch?>');</script>
		</div>
		<?}else{
			$dialogexplorer->dialog($explorer_lang['error_open']."*.$ext</b>",$explorer_lang['error_label'],"bounce");
		}
	}

$d = dir($dir);
chdir($d->path);
$warfile = array(".htaccess");
$pathmain = $d->path;

$prefix = 'os';

if ($pathmain == '../../../'){
	$pathmain = realpath($entry);
}

if($_SESSION['godmode'] == 'false'){
	$pathmain = str_replace($_SERVER['DOCUMENT_ROOT'], '', $pathmain);
	$back = $_SERVER['DOCUMENT_ROOT'].dirname($pathmain);
}

if($_SESSION['godmode'] == 'true'){
	$back = dirname($pathmain);
}

$pathmain = str_replace($_SERVER['DOCUMENT_ROOT'], '', $pathmain);

?>
<div style="position:absolute; width:100%; z-index:1; background:#f2f2f2; border:1px solid #d4d4d4; box-shadow: 0 1px 2px rgba(0,0,0,0.065);">

<div class="menucontainer" style="display: flex;">
<div class="ui-forest-menu-button" onmouseover="$('#filemenu<?echo $AppID?>').css('display','block')" onmouseout="$('#filemenu<?echo $AppID?>').css('display','none')">
	<span><?echo $explorer_lang['menu_file_label']?></span>
	<div id="filemenu<?echo $AppID?>" style="display:none; cursor:default; position:absolute; z-index:1; background:#fff; width:auto; top:31px; left:4px;">
<ul id="mmenu<?echo $AppID?>" >
	<li><div <?echo 'id="'.$dir.'/" class="loadthis'.$AppID.'" onClick="load'.$AppID.'(this);" ';?> ><?echo $explorer_lang['menu_open_label']?></div></li>
	<li>
		<div <?echo 'id="'.$dir.'/" class="loadas" ';?> ><?echo $explorer_lang['menu_openas_label']?></div>
		<ul style="background:#fff;">
			<?
			foreach (glob($_SERVER['DOCUMENT_ROOT']."/system/apps/*/main.php") as $filenames)
			{
				$get_name = preg_match('/apps.*?\/(.*?)\/main.php/',$filenames, $app_name);
			  $_app_name = $app_name[1];
			  $app_name = str_replace('_', ' ', $_app_name);
				echo '<li><div onClick="makeprocess(\''.$_SERVER['DOCUMENT_ROOT'].'/system/apps/'.$_app_name.'/main.php\',$(\'.loadas\').attr(\'id\'),\'defaultloader\',\''.$_app_name.'\');">'.$app_name.'</div></li>';
			}
			?>
		</ul>
	</li>
	<li><div <?echo 'class="loadthis'.$AppID.'" onClick="mkfileshow'.$AppID.'();" ';?> ><?echo $explorer_lang['menu_newfile_label']?></div></li>
	<li><div <? echo 'id="'.$dir.'/" class="loadthis'.$AppID.'" onClick="getproperty'.$AppID.'(this);"';?>><?echo $explorer_lang['menu_rename_label']?></div></li>
	<li><div <?echo 'onClick="mkdirshow'.$AppID.'();" ';?> ><?echo $explorer_lang['menu_md_label']?></div></li>
	<li><div <?echo 'id="'.$dir.'/" class="mklink" onClick="link'.$AppID.'(this);" ';?> ><?echo $explorer_lang['menu_ml_label']?></div></li>
	<li><div <?echo 'class="loadthis'.$AppID.'" onClick="newload'.$AppID.'('."'del'".',this.id)" ';?>><?echo $explorer_lang['menu_trash_label']?></div></li>
	<li><div <?echo 'class="loadthis'.$AppID.'" onClick="newload'.$AppID.'('."'delf'".',this.id)" ';?>><?echo $explorer_lang['menu_delete_label']?></div></li>
	<li><div <? echo 'id="'.$dir.'/" onClick="loadshow'.$AppID.'(this)"';?>><?echo $explorer_lang['menu_loadfile_label']?></div></li>
	<li><div <? echo 'class="loadthis'.$AppID.'" onClick="newload'.$AppID.'('."'zipfile'".',this.id)"';?>><?echo $explorer_lang['menu_zip_label']?></div></li>
	<li><div <? echo 'class="loadthis'.$AppID.'" onClick="newload'.$AppID.'('."'zipfileunpack'".',this.id)"';?>><?echo $explorer_lang['menu_zip_unpack']?></div></li>
	<li><div <? echo 'id="'.$dir.'/" class="loadthis'.$AppID.'" onClick="getproperty'.$AppID.'(this);"';?>><?echo $explorer_lang['menu_property_label']?></div></li>
</ul>
</div>
</div>

<div class="ui-forest-menu-button" onmouseover="$('#editmenu_<?echo $AppID?>').css('display','block')" onmouseout="$('#editmenu_<?echo $AppID?>').css('display','none')">
	<span><?echo $explorer_lang['menu_edit_label']?></span>
	<div id="editmenu_<?echo $AppID?>" style="display:none; cursor:default; position:absolute; z-index:1; background:#fff; width:auto; top:31px; left:68px;">
<ul id="editmenu<?echo $AppID?>" >
	<li><div <?echo 'id="" class="loadthis'.$AppID.'" onClick="copy'.$AppID.'(this.id);" ';?> ><?echo $explorer_lang['menu_copy_label']?></div></li>
	<li class="pastebutton"><div <?echo 'id="" class="loadthis'.$AppID.'" onClick="paste'.$AppID.'(this.id);" ';?> ><?echo $explorer_lang['menu_paste_label']?></div></li>
	<li><div <?echo 'id="" class="loadthis'.$AppID.'" onClick="cut'.$AppID.'(this.id);" ';?> ><?echo $explorer_lang['menu_cut_label']?></div></li>
</ul>
</div>
</div>
	<?

	//show select button if is mobile
	if($isMobile == 'true'){
		if(empty($_GET['select']) || $_GET['select'] == 'false'){
			echo '<div id="selectbutton-'.$AppID.'" onclick="selectButtonActive'.$AppID.'(true)" style="margin-top:2px;" class="ui-forest-button ui-forest-accept">'.$explorer_lang['selectButton'].'</div>';
		}else{
			echo '<div id="selectbutton-'.$AppID.'" onclick="selectButtonActive'.$AppID.'(false)" style="margin-top:2px;" class="ui-forest-button ui-forest-cancel">'.$explorer_lang['cancelButton'].'</div>';
		}
	}
	?>
</div>

<div style="margin-top:7px; border-top:1px solid #d4d4d4; padding-top:7px;">
<div class="ui-forest-blink" style="padding:4px; background:#4d94ef; margin:0px 10px; border-radius:10px; color:#2b5182; float:left; width:20px;" id="<?echo $back?>" onclick="load<?echo $AppID?>(this)">
	&#9668;
</div>
<input style="-webkit-appearance:none; border:1px solid #ccc; width:80%; font-size:17px; margin: 0 5px 10px;" type="search" value="<?echo $prefix.$pathmain?>"></input>
</div>
</div>
<div id="mkdirdiv<?echo $AppID?>" style="z-index:1; position:fixed; display:none; top:25%; left:25%; background-color:#ededed; border:1px solid #797979; padding:20px; border-radius:6px; box-shadow:1px 1px 5px #000; width:min-content; text-align:center;">
<label for="mkdirinput<?echo $AppID?>">
	<?echo $explorer_lang['mdir_label']?>
	<input id="mkdirvalue<?echo $AppID?>" style="font-size:20px; margin-bottom:10px;" name="mkdirinput<?echo $AppID?>" type="text" value="">
</label>
<span onclick="$('#mkdirdiv<?echo $AppID?>').css('display','none');" style="width:70px;" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mdir_cancelbtn']?>
</span>
<span style="width:70px;" onClick="mkdirbtn<?echo $AppID?>();" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mdir_okbtn']?>
</span>
</div>

<div id="mkfilediv<?echo $AppID?>" style="z-index:1; position:fixed; display:none; top:25%; left:25%; background-color:#ededed; border:1px solid #797979; padding:20px; border-radius:6px; box-shadow:1px 1px 5px #000; width:min-content; text-align:center;">
<label for="mkfileinput<?echo $AppID?>">
	<?echo $explorer_lang['mfile_label']?>
	<input id="mkfilevalue<?echo $AppID?>" style="font-size:20px; margin-bottom:10px;" name="mkfileinput<?echo $AppID?>" type="text" value="">
</label>
<span onclick="$('#mkfilediv<?echo $AppID?>').css('display','none');" style="width:70px;" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mfile_cancelbtn']?>
</span>
<span style="width:70px;" onClick="mkfilebtn<?echo $AppID?>();" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mfile_okbtn']?>
</span>
</div>

<div style="margin: 92px 0;">
<?
$countState = true;
$objectArray = array();

while (false !== ($entry = $d->read())) {
	$path	=	$d->path;
	$name	=	convert($entry);
	if ($entry	!=	'..'){
		$color	=	'transparent';
		$extension	=	'';
		$type	=	$Folder.'assets/folderico.png?h='.$hashImage;
		try {
			$fo->size_check(realpath(realpath($entry)));
			$fo->format($size);
			if (empty($size)){
				$format	= '0 Bytes';
			}
			$format = '<br> '.$explorer_lang['size'].': '.$format;
		} catch (Exception $e) {
			echo $e->getMessage($e);
		}

		$datecreate = $explorer_lang['date'].': '.date('d.m.y H:i:s', filectime(realpath($entry))).$format;
	}
	if(preg_match('/'.$_SESSION["loginuser"].'\/trash/',$pathmain)){
		?>
		<div id="erasetrash<?echo $AppID?>" onClick="erasetrash<?echo $AppID?>();" class="ui-forest-button ui-forest-cancel" style="margin:5px; padding:64px 10px; float:left; display:none; height:14px;">
			<b><?echo $explorer_lang['trash_label']?></b>
		</div>
		<script>
		$('#erasetrash<?echo $AppID?>').css('display','block');
		</script>
		<?
	}
	if(is_file(realpath($entry))){
		$object	=	$dialogexplorer;
		$color = 'rgba(0,0,0,0)';
		if($name	==	'main.php'){
			if(file_exists('app.png')){
				$hashfileprefix	= $faction->filehash('app.png','false');
				$type	=	$pathmain.'/'.$hashfileprefix;
			}else{
				$type	=	'system/core/design/images/app.png';
			}
			$extension	=	"";
		}else{
			$extension	=	stristr($name, '.');
			$extension	=	mb_strtolower(str_replace('.','',$extension));
			$type	=	$Folder.'assets/fileico.png?h='.$hashImage;
			if($extension	==	'png'  || $extension	==	'jpg' || $extension	==	'jpeg' || $extension	==	'bmp' || $extension	==	'gif'){
				$color = 'transparent';
				$hashfileprefix	= $faction->filehash($entry,'false');
				$type	=	$pathmain.'/'.$hashfileprefix;
				$extension	=	"";
			}
		}
		$fo->format(filesize(realpath($entry)));
		$datecreate = $explorer_lang['date'].': '.date('d.m.y H:i:s', filectime(realpath($entry))).'<br> '.$explorer_lang['size'].': '.$format;
	}

	if($countState){
		$wardir = $_SERVER['DOCUMENT_ROOT'];
		$wardir = stristr($wardir, 'public_html');
		$wardir	= str_replace('public_html/','',$wardir);
	}

	if ($entry != '.' && $entry != '..' && !in_array($entry, $warfile) && realpath($entry).'/'.$wardir != $_SERVER['DOCUMENT_ROOT']){
		$select	=	'select'.$AppID.'(\''.md5($name).'-'.$AppID.'\',\''.convert(realpath($entry)).'\',\''.$type.'\',\''.$name.'\');';
		$load = 'load'.$AppID.'(this);';
		$n_color	=	'#000';
		if(eregi('system/users/',realpath($entry)) || eregi('system/core',realpath($entry))){
			if($_SESSION['superuser'] != $_SESSION['loginuser'] && !eregi('system/users/'.$_SESSION['loginuser'],realpath($entry)) || $_SESSION['superuser'] != $_SESSION['loginuser'] && eregi('system/core',realpath($entry))){
			$select	=	'';
			$load = '';
			$n_color	=	'#e63030';
		}
	}

	//is mobile?
	if($isMobile == 'true' && empty($_GET['select']) || $_GET['select'] == 'false'){
		$action = 'click';
		$selectAction = 'ondblclick="'.$select.'"';
	}else{
		$action = 'dblclick';
		$selectAction = 'onclick="'.$select.'"';
	}

	//what is type object
	if(!is_file(realpath($entry))){
		$typeObject = 'dir';
	}else{
		$typeObject = 'file';
	}

	$objectArray[$typeObject] [] = urlencode('<div id="'.convert(realpath($entry)).'" class="'.md5($name).'-'.$AppID.' select'.$AppID.' ui-button ui-widget ui-corner-all explorer-object" '.$selectAction.' on'.$action.'="'.$load.'"  style="cursor:default; height:128px;	margin:5px;	text-align:center;	width:128px;	position:relative;	display:block;	text-overflow:ellipsis;	overflow:hidden;	float:left; transition:all 0.05s ease-out;" title="'.$name.'"><div style="cursor:default; width:80px; height:80px; background-image: url('.$type.'); background-size:cover; -webkit-user-select:none; user-select:none; padding:5px; background-color:'.$color.'; margin:auto;">
	<div style="margin-top:22px; color:#d05858; font-size:17px; font-weight:900;">
	'.$extension.'
		</div>
	</div>
	<div style="text-overflow: ellipsis;overflow: hidden;font-size: 15px;">
		<span style="color:'.$n_color.'; white-space:nowrap;">
			'.$name.'
		</span>
		<div style="font-size:10px; padding:5px; color:#688ad8;">
		'.$datecreate.'
		</div>
		</div>
		</div>');
}

$countState = false;
}
$dir->close;

//show dir first
foreach($objectArray as $type => $object){
	if($type == 'dir'){
		foreach($object as $dirObject){
			echo urldecode($dirObject);
		}
	}
}

//show files
foreach($objectArray as $type => $object){
	if($type == 'file'){
		foreach($object as $fileObject){
			echo urldecode($fileObject);
		}
	}
}

unset($objectArray);
?>
</div>
<div id="upload<?echo $AppID?>" style="z-index:1; position:fixed; display:none; top:25%; left:25%; background-color:#ededed; border:1px solid #797979; padding:20px; border-radius:6px; box-shadow:1px 1px 5px #000;">
</div>

<div style="padding:0 10px; background-color:#f2f2f2; width:97%; top:97%; word-wrap:break-word; font-size:10px; float:right; position:absolute; text-align:right;">
<?
$fo->size_check(dirname(dirname(dirname(__DIR__))));
$fo->format($size);
echo $explorer_lang['use_label'].': '.$format;
?>
</div>
<?
$AppContainer->EndContainer();
?>
<script>

<?

	// load dir
	$AppContainer->Event(
		"load",
		'object',
		$Folder,
		'main',
		array(
			'mobile' => $isMobile,
			'dir' => '"+object+"',
			'select' => $_GET['select']
		),
		'if(typeof object === \'string\' || object instanceof String){object = object;}else{object = object.id;}',
		0
	);

	// make link
	$AppContainer->Event(
		"link",
		'object',
		$Folder,
		'main',
		array(
			'linkdir' => '"+object.id+"',
			'ico' => '"+object.getAttribute(\'ico\')+"',
			'linkname' => '"+object.getAttribute(\'link\')+"',
			'dir' => realpath($entry),
			'select' => $_GET['select']
		)
	);

	// show load div
	$AppContainer->Event(
		"loadshow",
		'object',
		$Folder,
		'uploadwindow',
		array(
			'where' => '"+object.id+"',
			'select' => $_GET['select']
		),
		'$("#upload'.$AppID.'").css(\'display\', \'block\');',
		1,
		"upload$AppID"
	);

	// erase trash
	$AppContainer->Event(
		"erasetrash",
		NULL,
		$Folder,
		'main',
		array(
			'erasestatus' => 'true',
			'dir' => realpath($entry),
			'select' => $_GET['select']
		)
	);

	// make dir button
	$AppContainer->Event(
		"mkdirbtn",
		NULL,
		$Folder,
		'main',
		array(
			'makedir' => '"+escape($("#mkdirvalue'.$AppID.'").val())+"',
			'dir' => realpath($entry),
			'select' => $_GET['select']
		)
	);

	// make file button
	$AppContainer->Event(
		"mkfilebtn",
		NULL,
		$Folder,
		'main',
		array(
			'makefile' => '"+escape($("#mkfilevalue'.$AppID.'").val())+"',
			'dir' => realpath($entry),
			'select' => $_GET['select']
		)
	);

	// new load ???
	$AppContainer->Event(
		"newload",
		'key,value',
		$Folder,
		'main',
		array(
			'"+key+"' => '"+value+"',
			'dir' => realpath($entry),
			'select' => $_GET['select']
		)
	);

	// reload
	$AppContainer->Event(
		"reload",
		NULL,
		$Folder,
		'main',
		array(
			'dir' => realpath($entry),
			'select' => $_GET['select']
		)
	);

	// select | deselect
	$AppContainer->Event(
		"selectButtonActive",
		'state',
		$Folder,
		'main',
		array(
			'dir' => realpath($entry),
			'select' => '"+state+"'
		)
	);
?>

function getproperty<?echo $AppID?>(obj){
	makeprocess('<?echo $Folder?>property.php', obj.id, 'object', '<?echo $explorer_lang['menu_property_label']?>');
};

var enterfolder;
var backfolder = "<?echo $back?>";
let rightfolder = null;

let keycode = null;
let e = null;

function select<?echo $AppID?>(folder, folder2, folder3, folder4){
	$(".select<?echo $AppID?>").css('background-color','transparent');
	$('.'+folder).css('background-color','#d4d4d4');
	$(".loadthis<?echo $AppID?>").attr("id",folder2);
	$(".loadas").attr("id",folder2);
	$(".mklink").attr("id",folder2);
	$(".mklink").attr("ico",folder3);
	$(".mklink").attr("link",folder4);
	enterfolder = folder2;
	rightfolder = $('.'+folder).next();
	if(rightfolder.attr('class')){
		rightfolder = rightfolder.attr('class').split(' ')[0];
	}else{
		rightfolder = $('.select<?echo $AppID?>').attr('class').split(' ')[0];
	}
	console.log(rightfolder);
};

function mkdirshow<?echo $AppID?>(){
	$("#mkdirdiv<?echo $AppID?>").css('display','block')
};

function mkfileshow<?echo $AppID?>(){
	$("#mkfilediv<?echo $AppID?>").css('display','block')
};

function checkbutton(){
	if(localStorage.getItem('copy') == null && localStorage.getItem('cut') == null){
		$('.pastebutton').css({
			'pointer-events' : 'none',
			'opacity' : '0.6'
		});
	}else{
		$('.pastebutton').css({
			'pointer-events' : 'all',
			'opacity' : '1'
		});
	}
}

function copy<?echo $AppID?>(file){
	localStorage.setItem('copy', file);
	checkbutton();
};

function paste<?echo $AppID?>(file){
	var getFile = localStorage.getItem('copy');
	var action = '';
	if(getFile != null){
		action = 'copy';
	}else{
		getFile = localStorage.getItem('cut');
		localStorage.removeItem('cut');
		action = 'cut';
	}

	$.ajax({
		type: "POST",
		url: "system/core/functions/filesystem",
		data: {
			 f:getFile,
			 n:"<?echo convert(realpath($entry)).'/'?>",
			 a:action
		}
	}).done(function(o) {
		reload<?echo $AppID?>();
});
	checkbutton();
};

function cut<?echo $AppID?>(file){
	localStorage.removeItem('copy');
	localStorage.setItem('cut', file);
	checkbutton();
};

$(function(){
	$("#editmenu<?echo $AppID?>").menu();
	$("#mmenu<?echo $AppID?>").menu();
	$("#makeprocess").remove();
});

function reloadApp<?echo $AppID?>(){
	reload<?echo $AppID?>();
}

	$("#app<?echo $AppID?>").bind('keyup', function(e){
		if($("#app<?echo $AppID?>").hasClass('windowactive')){
			var keycode = (e.keyCode ? e.keyCode : e.which);

			//check if enter pressed
			if(enterfolder){
				if(keycode == '13'){
					load<?echo $AppID?>(enterfolder);
					keycode = null;
					enterfolder = null;
					e = null;
				}
			}

			//check if back pressed
			if(backfolder){
				if(keycode == '8'){
					load<?echo $AppID?>(backfolder);
					keycode = null;
					backfolder = null;
					e = null;
				}
		}

		//check if right pressed
		if(keycode == '39'){
			if(rightfolder){
				if($("."+rightfolder).trigger('click')){
					keycode = null;
					e = null;
				}
			}else{
				rightfolder = $('.select<?echo $AppID?>').attr('class').split(' ')[0];
				if($("."+rightfolder).trigger('click')){
					keycode = null;
					e = null;
				}
			}
		}

	}else{
		keycode = null;
		enterfolder = null;
		backfolder = null;
		rightfolder = null;
		e = null;
	}

	});



checkbutton();
</script>
<style>.ui-menu{width: 150px;}</style>
