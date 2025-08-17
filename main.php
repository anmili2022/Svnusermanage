<?php
session_start();

// 检查是否已经登录
if (isset($_SESSION['isManage']) && $_SESSION['isManage']) {
    $isManage = true;
} else {
    $isManage = false;
}

// 获取请求参数
$passwdfile = "D:/AEACR3.0/htpasswd"; // svn密码所在文件
$usersfile = "./data/users.data"; // 用户备注所在文件
$action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : ''; // action
$u = isset($_REQUEST["u"]) ? $_REQUEST["u"] : ''; // 用户名
$postRemark = isset($_REQUEST["remark"]) ? $_REQUEST["remark"] : ''; // 备注
$postPassword = isset($_REQUEST["password"]) ? $_REQUEST["password"] : ''; // 密码

// 初始化变量
$success = false;
$message = ""; // 确保初始化为空字符串
$users = array(); // 初始化 $users 为空数组

echo "action: " . $action . "<br>";

//注销
if ($action=="logout")
{
    // 清除会话变量
    $_SESSION = array();

    // 销毁会话
    session_destroy();

    // 删除会话的Cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    // 重定向到登录页面
    header('Location: usermanage.php');
    exit();
}

// 登录逻辑
// 定义允许的用户名和密码
$allowedUsers = [
    "admin" => "admin123",
    "admin2" => "admin1234"
];
if (isset($_POST['login'])) {
    $un = isset($_POST["un"]) ? $_POST["un"] : ''; // 用户名
    $pw = isset($_POST["pw"]) ? $_POST["pw"] : ''; // 密码

    if (isset($allowedUsers[trim($un)]) && trim($pw) == $allowedUsers[trim($un)]) {
        $_SESSION['isManage'] = true;
        $_SESSION['NAME'] = trim($un);
        $isManage = true;
        $message = "登录成功！";
    } else {
        $message = "用户名或密码错误！";
    }
}
        $passwordfilelines = file($passwdfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$usersfilelines = file($usersfile);
		$users = array();
	    foreach($passwordfilelines as $line => $content){
	    	$temps = array();
	    	$obj = explode(':', $content);
			array_push($temps,$obj[0]);
	        foreach($usersfilelines as $line1 => $content1){
	        	$obj1 = explode(':', $content1);
		    	if($obj[0] == $obj1[0]){
		    		array_push($temps,$obj1[1]);
		    	}			        
		    }
			if(count($temps)==1){
				array_push($temps,"");
			}
			array_push($users,$temps);
	    }
// 新建用户逻辑
if ($isManage && $action == "add" && $u !="" && $postRemark !="") {
    $u = isset($_POST["u"]) ? $_POST["u"] : ''; // 用户名
    $postRemark = isset($_POST["remark"]) ? $_POST["remark"] : ''; // 备注
    $postPassword = isset($_POST["password"]) ? $_POST["password"] : ''; // 密码

    // 对用户输入进行验证
    $u = htmlspecialchars($u, ENT_QUOTES, 'UTF-8');
    $postRemark = htmlspecialchars($postRemark, ENT_QUOTES, 'UTF-8');
    $postPassword = htmlspecialchars($postPassword, ENT_QUOTES, 'UTF-8');
    //新建备注
    // 读取用户备注文件
    $usersfilelines = file($usersfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $users = array();
    foreach ($usersfilelines as $line) {
        $obj = explode(':', $line, 2); // 限制分割次数为2，防止备注中包含冒号
        $users[$obj[0]] = isset($obj[1]) ? $obj[1] : '';
    }

    // 更新备注
    $users[$u] = $postRemark;

    // 写入用户备注文件
    $myfile = fopen($usersfile, "w");
    if ($myfile === false) {
        echo "无法打开文件！";
        exit();
    }
    //新建密码
    $command='"D:/phpStudy/Apache/bin/htpasswd.exe" -b '.$passwdfile." ".$u." ".$postPassword;//执行新建命令

    system($command, $result);
    echo "command: " . $command . "<br>  " . $result;
    if($result==0){
    $message="用户[".$username."]新建成功";
    }
    else{
    $message="用户[".$username."]新建失败，返回值为".$result."，请和管理员联系！";
    }
    
}

// 修改密码和备注逻辑
if ($isManage && $action == "edit2" && $u !="" && $postRemark !="") {
    
    $u = isset($_GET["u"]) ? $_GET["u"] : ''; // 用户名
    $postRemark = isset($_GET["remark"]) ? $_GET["remark"] : ''; // 备注
    $postPassword = isset($_GET["password"]) ? $_GET["password"] : ''; // 密码

    // 对用户输入进行验证
    $postRemark = htmlspecialchars($postRemark, ENT_QUOTES, 'UTF-8');
    $u = htmlspecialchars($u, ENT_QUOTES, 'UTF-8');

    //修改备注
    // 读取用户备注文件
    $usersfilelines = file($usersfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $users = array();
    foreach ($usersfilelines as $line) {
        $obj = explode(':', $line, 2); // 限制分割次数为2，防止备注中包含冒号
        $users[$obj[0]] = isset($obj[1]) ? $obj[1] : '';
    }

    // 更新备注
    // 初始化更新成功标志
    $success = false;
    $users[$u] = $postRemark;

    // 写入用户备注文件
    $myfile = fopen($usersfile, "w");
    if ($myfile === false) {
        echo "无法打开文件！";
        exit();
    }

    foreach ($users as $username => $remark) {
        fwrite($myfile, $username . ":" . $remark . "\n");
    }
    fclose($myfile);
    // 更新成功
    $success = true;

    if ($success) {
        $message="用户[".$u."]备注更新成功！";
    } else {
        $message="用户[".$u."]备注更新失败！";
    }

    if ($postPassword != ""){
        // 修改密码
        $command='"D:/phpStudy/Apache/bin/htpasswd.exe" -b '.$passwdfile." ".$u." ".$postPassword;//执行修改命令

        system($command, $result);
        echo "command: " . $command . "<br>执行结果：" . $result."<br>";
        if($result==0){
        $message=$message."用户[".$u."]密码修改成功";
        }
        else{
        $message=$message."用户[".$u."]密码修改失败，返回值为".$result."，请和管理员联系！";
        }
    }
    echo "message=" . $message;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>SVN用户管理WEB界面</title>
    <style type="text/css">
        td{border:solid #CCCCCC; border-width:0px 1px 1px 0px; padding:10px 0px;}
        table{border:solid #CCCCCC; border-width:1px 0px 0px 1px; background-color: #f9f9f9;}
    </style>
</head>
<body>
<?php

if ($isManage)
{
    echo "管理员：".$_SESSION['NAME']."<br>";
}
?>

<a href="?action=logout">注销</a>&nbsp;&nbsp;&nbsp;<a href="?action=add">新建用户</a>
<?php if ($action == "add" || $action == "edit"): ?>
        <form method="get">
            <br/><br/><br/>新建/修改用户：
            <table width="100%" border="0" cellspacing="1" cellpadding="0">
                <tr>
                    <td>用户名</td>
                    <td><input name="u" type="text" value="<?=$u?>" /></td>
                </tr>
                <tr>
                    <td>备注</td>
                    <td>
                        <textarea name="remark" style="width: 50%; height: 100px;"><?=$postRemark?></textarea>
                    </td>
                </tr>
                <tr>
                    <td>密码(为空则不修改)</td>
                    <td><input name="password"/></td>
                </tr>
                <input type="hidden" name="action" value="edit2">
                <tr>
                    <td colspan="2" align="center">
                        <input type="submit" value="修 改">
                        <input name="reset" type="reset" value="取 消">
                        <p>
                            <input name="reset" type="button" value="返回" style="width:115px;" onclick="javascript:history.go(-1);">
                        </p>
                    </td>
                </tr>
            </table>
        </form>
    <?php endif; ?>

    <?php if (!$isManage): ?>
    <form method="post">
        <h2>管理员登录</h2>
        <label for="un">用户名:</label>
        <input type="text" id="un" name="un" required><br><br>
        <label for="pw">密码:</label>
        <input type="password" id="pw" name="pw" required><br><br>
        <input type="submit" name="login" value="登录">
    </form>
    <?php 
    exit();
    endif; 
    if ($message!="")
    {
        ?>
        <script language="javaScript">
        <!--
        alert("<?=$message?>");
        window.location.href="usermanage.php"
        //-->
        </script>
        <?php
    }
    ?>
    
    
    <table width="100%" border="0" cellspacing="1" cellpadding="0">
        <tr>
            <td>
                用户名
            </td>
            <td>
                备注
            </td>
            <td align="center">
                操作
            </td>
        </tr>
        <?php foreach ($users as $key => $value): ?>
        <tr>
            <td>
                <?=$value['0'] ?>
            </td>
            <td>
                <?=$value['1'] ?>
            </td>
            <td align="center">
                <a href="?action=edit&u=<?=$value['0']?>&remark=<?=$value['1']?>">修改</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
