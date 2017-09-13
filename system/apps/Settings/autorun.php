<?
//Инициализируем переменные
$appid  = $_GET['appid'];
$appname  = $_GET['appname'];
$folder = $_GET['destination'];
$checked  = $_GET['checked'];
$savestatus  = $_GET['save'];
?>
<div id="<?echo $appname.$appid;?>" style="text-align:center; background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span>Менеджер автозапуска</div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/gui.php';
$newgui = new gui;
session_start();
if($savestatus=='true'){
  file_put_contents('../../users/'.$_SESSION["loginuser"].'/settings/autorun.foc',$checked);
  if(empty($checked)){
    $text = "Теперь в менеджере автозапуска пусто";
  }else{
    $text = "Вы добавили в автозапуск: <b>$checked</b>";
  }
  $newgui->newnotification($appname,"Менеджер автозапуска",$text);
  unset($text);
}
echo '<div class="checkboxfix" style="width:80%; text-align:left; margin:15px auto;"><fieldest>';
$i=0;
foreach (glob("../*/main.php") as $filename)
{
  $i++;
  $name = str_replace(array('main.php','..','/'),'',$filename);
  $pubname  = str_replace('_',' ',$name);
  echo '<label for="checkbox'.$name.$appid.'">'.$pubname.'</label>';
  echo '<input type="checkbox" class="checkboxclass'.$appid.'" id="checkbox'.$name.$appid.'" name="'.$name.'">';
}
echo '</fieldest></div>';
echo '<hr><div onClick="saveautoset'.$appid.'();" class="ui-forest-button ui-forest-accept ui-forest-center">Сохранить</div>';

$content  = file_get_contents('../../users/'.$_SESSION["loginuser"].'/settings/autorun.foc');
if($content){
  $array  = explode(",",$content);
  foreach ($array as $value){
    ?>
    <script>
    $("#checkbox<?echo $value.$appid?>").prop("checked",true);
    </script>
    <?
  }
}
?>
</div>
<script>
$(function(){
  $(".checkboxclass<?echo $appid;?>").checkboxradio({
    icon: false
  });
});
function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
function saveautoset<?echo $appid;?>(){
  var checkboxradio<?echo $appid;?> = [];
  $('.checkboxclass<?echo $appid;?>:checked').each(function(){
    checkboxradio<?echo $appid;?>.push(this.name);
  });
  $("#<?echo $appid;?>").load("<?echo $folder?>autorun.php?checked="+escape(checkboxradio<?echo $appid;?>)+"&save=true&id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>");
};
</script>
<style>
.checkboxfix label{
  padding:20px 5px;
  margin: 5px;
  width:100px;
  border-radius: 10px;
}
</style>