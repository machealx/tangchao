<?php
#注册插件
include 'usertable.php';
include 'yt_event.php';
RegisterPlugin("YtUser","ActivePlugin_YtUser");

function ActivePlugin_YtUser() {
	Add_Filter_Plugin('Filter_Plugin_Zbp_MakeTemplatetags','YtUser_Main');
	Add_Filter_Plugin('Filter_Plugin_Index_Begin','YtUser_page');
	Add_Filter_Plugin('Filter_Plugin_Html_Js_Add', 'YtUser_SyntaxHighlighter_print');
	Add_Filter_Plugin('Filter_Plugin_Admin_TopMenu', 'YtUser_AddMenu');
	Add_Filter_Plugin('Filter_Plugin_ViewPost_Template','YtUser_Content');
	Add_Filter_Plugin('Filter_Plugin_Edit_Response3','YtUser_Edit');
}

function YtUser_AddMenu(&$m) {
	global $zbp;
	$m[] = MakeTopMenu("root", '用户中心', $zbp->host . "zb_users/plugin/YtUser/main.php?act=base", "", "topmenu_metro");
}

function YtUser_Main(){
	global $zbp;
    if($zbp->user->ID){}else{$zbp->header .=  '<link rel="stylesheet" type="text/css" href="'.$zbp->host.'zb_users/plugin/YtUser/ytuser.css"/>' . "\r\n";}
    $data='<script type="text/javascript">var duoshuoQuery = {short_name:"'.$zbp->Config('YtUser')->dsurl.'",sso: { login: "'.$zbp->host.'zb_users/plugin/YtUser/login.php",logout: "'.$zbp->host.'zb_users/plugin/YtUser/logout.php/"}};(function() {var ds = document.createElement(\'script\');ds.type = \'text/javascript\';ds.async = true;ds.src = (document.location.protocol == \'https:\' ? \'https:\' : \'http:\') + \'//static.duoshuo.com/embed.js\';ds.charset = \'UTF-8\';(document.getElementsByTagName(\'head\')[0] || document.getElementsByTagName(\'body\')[0]).appendChild(ds);})();</script>';
	$zbp->footer .= $data. "\r\n";
}

function YtUser_Edit(){
	global $article;
	echo '<div id="price" class="editmod"><label for="edtIslock" class="editinputname">价格</label><input type="text" name="meta_price" id="price" value="'.(int)$article->Metas->price.'" style="width:180px;" class="hasDatepicker"></div>';
    echo '<div id="price" class="editmod">付费内容用[BuyView][/BuyView]包起来</div>';
}
function YtUser_Content(&$template){
	global $zbp;
	$article = $template->GetTags('article');
    $content = $article->Content;
    $userid = $article->ID;
	if($article->Type==ZC_POST_TYPE_ARTICLE){
		if((int)$article->Metas->price > 0){
        $sql=$zbp->db->sql->Select($GLOBALS['YtUser_buy_Table'],'*',array(array('=','buy_LogID',$article->ID),array('=','buy_AuthorID',$zbp->user->ID),array('=','buy_State',1)),null,1,null);
        $array=$zbp->GetListCustom($GLOBALS['YtUser_buy_Table'],$GLOBALS['tyactivate_DataInfo'],$sql);
        $num=count($array);
            if($num){
                $article->Buypay = 1;
                $content = preg_replace("/\[(.*?)BuyView\]/sm",'',$content);
            }else{
                $content = preg_replace("/\[BuyView\](.*?)\[\/BuyView\]/sm",'<p>****此部分是付费内容***</p><p><input type="hidden" name="LogID" id="LogID" value="'.$userid.'" /><input type="submit" style="width:100px;font-size:1.0em;padding:0.2em" value="购买" onclick="return Ytbuy()" /></p>',$content);
		    	$zbp->header .='<script src="'.$zbp->host.'zb_users/plugin/YtUser/Upgrade.js" type="text/javascript"></script>' . "\r\n";
		    }
        $article->Content = $content;
        $sql=$zbp->db->sql->Select($GLOBALS['tysuer_Table'],'*',array(array('=','tc_uid',$zbp->user->ID)),null,array(1),null);
        $array=$zbp->GetListCustom($GLOBALS['tysuer_Table'],$GLOBALS['tysuer_DataInfo'],$sql);
        $num=count($array);
        if($num==0){
            $article->Vipendtime =0;
        }else{
            $reg=$array[0];
            $article->Vipendtime = $reg->Vipendtime;
        }
        $sql=$zbp->db->sql->Select($GLOBALS['YtUser_buy_Table'],'*',array(array('=','buy_LogID',$article->ID),array('=','buy_State',1)),null,1,null);
        $array=$zbp->GetListCustom($GLOBALS['YtUser_buy_Table'],$GLOBALS['tyactivate_DataInfo'],$sql);
        $num=count($array);
        $article->paysum = $num+$article->Metas->paysum;
		$template->SetTags('article', $article);
		}
	}

}

function YtUser_SyntaxHighlighter_print() {
    global $zbp;
    if (!$zbp->option['ZC_SYNTAXHIGHLIGHTER_ENABLE']) {
        return;
    }
    $zbp->Load();
    if($zbp->user->ID){echo '$(function() {var $cpLogin = $(".cp-login").find("a");var $cpVrs = $(".cp-vrs").find("a");$(".cp-hello").html("欢迎 '.$zbp->user->StaticName.'  <a href=\"'.$zbp->host.'?Articleedt\">投稿</a>");$cpLogin.html("会员中心");$cpLogin.attr("href", bloghost + "?User");$cpVrs.html("评论列表");$cpVrs.attr("href", bloghost + "?Commentlist");});';}else{echo'$(function () {$(".cp-login").html("<p><a href=\"'.$zbp->host.'?Login\">会员登录</a><a href=\"'.$zbp->host.'?Register\">会员注册</a><p>");$(".cp-vrs").html("<div class=\"ds-login\"></div>");$(".cp-hello").hide();$("#divContorPanel br").hide();$("#divContorPanel").each(function() { var text = $(this).html().replace(/&nbsp;/g, "");text = text;$(this).html(text);});});';}
}

function InstallPlugin_YtUser() {
	global $zbp;
    YtUser_CreateTable();
	$zbp->Config('YtUser')->dsurl = 'zbloguser';
    $zbp->Config('YtUser')->default_level=4;
	$zbp->SaveConfig('YtUser');
}

function UninstallPlugin_YtUser() {
}

//创建表存用户数据
function YtUser_CreateTable(){
    global $zbp;
    $s=$zbp->db->sql->CreateTable($GLOBALS['tysuer_Table'],$GLOBALS['tysuer_DataInfo']);
    $zbp->db->QueryMulit($s);
    $s=$zbp->db->sql->CreateTable($GLOBALS['tyactivate_Table'],$GLOBALS['tyactivate_DataInfo']);
    $zbp->db->QueryMulit($s);
    $s=$zbp->db->sql->CreateTable($GLOBALS['typrepaid_Table'],$GLOBALS['typrepaid_DataInfo']);
    $zbp->db->QueryMulit($s);
    $s=$zbp->db->sql->CreateTable($GLOBALS['YtUser_buy_Table'],$GLOBALS['YtUser_buy_DataInfo']);
    $zbp->db->QueryMulit($s);
}
