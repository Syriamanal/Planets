<?php
/*
__PocketMine Plugin__
name=Planet
version=1.0.0-Beta
author=Kevin Wang
class=KVPlanet
apiversion=11,12
*/

/* 

By Kevin Wang
From China

Project Website: http://www.MineConquer.com/
Official Website: http://www.cnkvha.com/
Skype: kvwang98
Twitter: KevinWang_China
Youtube: http://www.youtube.com/VanishedKevin
E-Mail: kevin@cnkvha.com

*/

/*
Data Structures: 
Mines{
    [MineName](String, LowerCase){
        Level(String) = World Name
        Pos1{
            x,
            y,
            z
        }
        Pos2{
            x,
            y,
            z
        }
        TeleportPos{
            x,
            y,
            z
        }
        Blocks{
            [BlockID]-[BlockMeta] : Chance(Number) = How much percent this block will appear
            ...
        }
    }
    ...
}
*/

class KVPlanet implements Plugin{
	private $api;
    //private $mines = array();
    private $CHAT_PREFIX = "● [Planet]";
    //private $SESSION = "prison_mine";
    private $presetFolderName = "planetpreset";
    private $presetMine = "minepreset";
    private $mineName = "mines";
    private $mineResetWarpName = "planets";
    private $maxHelpPages = 2;
    
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}
	
	public function init(){
        //Default values
        //$this->api->session->setDefaultData($this->SESSION, array("Pos1" => array(-1,-1,-1), "Pos2" => array(-1,-1,-1), "TeleportPos" => array(-1,-1,-1)));
        //Get the config
        /*
        $cfg = new Config($this->api->plugin->configPath($this)."Mines.yml", CONFIG_YAML, array("Mines" => array()));
        $this->mines = $cfg->get("Mines");
        unset($cfg);
        */
        //Register events
        $this->api->addHandler("pmess.signs.tap", array($this, "handleSignsTap"), 5);
        $this->api->addHandler("pmess.signs.other.denytextchange", array($this, "handleSignsTextChange"), 5);
        $this->api->console->register("mine", "Prison Mine commands. ", array($this, "handleMineCommand"));
        $this->api->schedule(12*60*60*20, array($this, "timerMineReset"), array(), true);
        $this->api->schedule(10*20, array($this, "timerUnloadLevels"), array(), true);
	}
	
    public function handleMineCommand($cmd, $arg, $issuer, $alias){
        //if(!($issuer instanceof Player)){return("Please run this command in-game. ");}
        if(count($arg) < 1){
            return($this->getHelp(1));
        }
        $c = strtolower(array_shift($arg));
        switch($c){
            /*
            case "help":
                if(count($arg) != 1){
                    return($this->getHelp(1));
                }else{
                    return($this->getHelp($arg[0]));
                }
                break;
            case "pos1":
                $this->api->session->sessions[$issuer->CID][$this->SESSION]["Pos1"] = array((int)$issuer->entity->x, (int)$issuer->entity->y, (int)$issuer->entity->z);
                return($this->CHAT_PREFIX . "Position 1 is set! ");
                break;
            case "pos2":
                $this->api->session->sessions[$issuer->CID][$this->SESSION]["Pos2"] = array((int)$issuer->entity->x, (int)$issuer->entity->y, (int)$issuer->entity->z);
                return($this->CHAT_PREFIX . "Position 2 is set! ");
                break;
            case "tppos": 
                $this->api->session->sessions[$issuer->CID][$this->SESSION]["TeleportPos"] = array((int)$issuer->entity->x, (int)$issuer->entity->y, (int)$issuer->entity->z);
                return($this->CHAT_PREFIX . "Teleport Position is set! ");
                break;
            case "create":
                if(count($arg) != 1){
                    return($this->CHAT_PREFIX . "Usage: \n/mine create [Name]");
                }
                if($this->checkValidPos($this->api->session->sessions[$issuer->CID][$this->SESSION]["Pos1"])==false or
                   $this->checkValidPos($this->api->session->sessions[$issuer->CID][$this->SESSION]["Pos2"])==false or
                   $this->checkValidPos($this->api->session->sessions[$issuer->CID][$this->SESSION]["TeleportPos"])==false
                ){
                    return($this->CHAT_PREFIX . "Please set 2 positions and 1 teleport points! ");
                }
                $mineName = strtolower($arg[0]);
                if($this->mineExists($mineName)){
                    return($this->CHAT_PREFIX . "Mine already exists! ");
                }
                $this->mines[$mineName] = array();
                $this->mines[$mineName]["Level"] = $issuer->level->getName();
                $this->mines[$mineName]["Pos1"] = $this->api->session->sessions[$issuer->CID][$this->SESSION]["Pos1"];
                $this->mines[$mineName]["Pos2"] = $this->api->session->sessions[$issuer->CID][$this->SESSION]["Pos2"];
                $this->mines[$mineName]["TeleportPos"] = $this->api->session->sessions[$issuer->CID][$this->SESSION]["TeleportPos"];
                $this->mines[$mineName]["Blocks"] = array();
                $this->saveConfig();
                return($this->CHAT_PREFIX . "Mine " . $mineName . " created! ");
                break;
            case "set":
                if(count($arg) != 4){
                    return($this->CHAT_PREFIX . "Usage: \n/mine set [Name] [BlockID] [Meta] [Chance]");
                }
                $mineName = strtolower(array_shift($arg));
                $blockID = (int)array_shift($arg);
                $meta = (int)array_shift($arg);
                $chance = (int)array_shift($arg);
                if($this->mineExists($mineName) == false){
                    return($this->CHAT_PREFIX . "Mine doesn't exist! ");
                }
                $this->mines[$mineName]["Blocks"][$blockID . "-" . $meta] = $chance;
                $this->saveConfig();
                return($this->CHAT_PREFIX . "Mine " . $mineName . " now has " . $chance . "% to have " . $blockID . ":" . $meta);
                break;
            case "unset":
                if(count($arg) != 3){
                    return($this->CHAT_PREFIX . "Usage: \n/mine unset [Name] [BlockID] [Meta]");
                }
                $mineName = strtolower(array_shift($arg));
                $blockID = (int)array_shift($arg);
                $meta = (int)array_shift($arg);
                if($this->mineExists($mineName) == false){
                    return($this->CHAT_PREFIX . "Mine doesn't exist! ");
                }
                if(isset($this->mines[$mineName]["Blocks"][$blockID . "-" . $meta])){
                    unset($this->mines[$mineName]["Blocks"][$blockID . "-" . $meta]);
                    $this->saveConfig();
                    return($this->CHAT_PREFIX . "All blocks(" . $blockID . ":" . $meta . ") removed from mine " . $mineName . ". ");
                }else{
                    return($this->CHAT_PREFIX . "No block exists in mine " . $mineName . ". ");
                }
                break;
                */
            case "reset":
                /*
                if(count($arg) != 1){
                    return($this->CHAT_PREFIX . "Usage: \n/mine reset [Name]");
                }
                $mineName = strtolower(array_shift($arg));
                if($this->mineExists($mineName) == false){
                    return($this->CHAT_PREFIX . "Mine doesn't exist! ");
                }
                */
                //$this->resetMine($mineName);
                $this->timerMineReset();
                break;
            default:
                return($this->CHAT_PREFIX . "Invalid arguments. \n" . $this->getHelp(1));
                break;
        }
    }
    
    
    public function timerMineReset(){
        /*
        foreach($this->mines as $mineName => $mineData){
            $this->resetMine($mineName);
        }
        */
        $this->api->chat->send(false, $this->CHAT_PREFIX . "Mine is being reset...");
        $level = $this->api->level->get($this->mineName);
        if(!($level instanceof Level)){
            $this->api->chat->send(false, $this->CHAT_PREFIX . "Mine faild to reset! [Reset every 24 hours]");
        }
        $pos = $this->api->warp->getWarp($this->mineResetWarpName);
        if($pos instanceof Position){
            foreach($level->players as $pIndex => $p){
                $p->teleport($p);
            }
        }
        $this->api->level->unloadLevel($level, true);
        $this->api->file->deleteFolder(FILE_PATH . "worlds/" . $this->mineName);
        $this->api->file->copyFolder(FILE_PATH . $this->presetMine, FILE_PATH . "worlds/" . $this->mineName);
        $this->api->level->loadLevel($this->mineName);
        $this->api->chat->send(false, $this->CHAT_PREFIX . "Mine has been reseted! [Reset every 24 hours]");
    }

    
    
    /*
    public function resetMine($mineName){
        $mineName = strtolower($mineName);
        if(!($this->mineExists($mineName))){return;}
        $level = $this->api->level->get($this->mines[$mineName]["Level"]);
        if(!($level instanceof Level)){return;}
        $blocksCount = count($this->mines[$mineName]["Blocks"]);
        if(count($blocksCount) < 1){return;}
        $blockList = $this->mines[$mineName]["Blocks"];
        $maxBlocks = array();
        $addCount = array();
        $blockUpdates = array();
        $blockUpdatesKeys = array();
        $blockKeys = array_keys($blockList);
        $startX = $this->mines[$mineName]["Pos1"][0];
        $startY = $this->mines[$mineName]["Pos1"][1];
        $startZ = $this->mines[$mineName]["Pos1"][2];
        $endX = $this->mines[$mineName]["Pos2"][0];
        $endY = $this->mines[$mineName]["Pos2"][1];
        $endZ = $this->mines[$mineName]["Pos2"][2];
        if($endX < $startX){list($startX, $endX) = array($endX, $startX);}
        if($endY < $startY){list($startY, $endY) = array($endY, $startY);}
        if($endZ < $startZ){list($startZ, $endZ) = array($endZ, $startZ);}
        $allCount = ($endX - $startX + 1) * ($endY - $startY + 1) * ($endZ - $startZ + 1);
        foreach($blockList as $bInfo => $bChance){
            $maxBlocks[$bInfo] = (int)($allCount * $bChance * 0.01);
            $addCount[$bInfo] = 0;
        }
        for($x = $startX; $x < $endX + 1; $x++){
            for($y = $startY; $y < $endY + 1; $y++){
                for($z = $startZ; $z < $endZ + 1; $z++){
                    $randIndex = mt_rand(0, $blocksCount-1);
                    $blockAddInfo = explode("-", $blockKeys[$randIndex]);
                    $blockAdd = BlockAPI::get((int)$blockAddInfo[0], (int)$blockAddInfo[1]);
                    //$level->setBlockRaw(new Vector3($x, $y, $z), $blockAdd);
                    $blockUpdates[] = $blockAdd;
                    $addCount[$blockKeys[$randIndex]]++;
                    if($addCount[$blockKeys[$randIndex]] > $maxBlocks[$blockKeys[$randIndex]]){
                        unset($blockList[$blockKeys[$randIndex]]);
                        unset($maxBlocks[$blockKeys[$randIndex]]);
                        unset($addCount[$blockKeys[$randIndex]]);
                        unset($blockKeys[$randIndex]);
                        $blockKeys = array_values($blockKeys);
                        $blocksCount--;
                    }
                }
            }
        }
        $blockUpdatesKeys = array_keys($blockUpdates);
        for($x = $startX; $x < $endX; $x++){
            for($y = $startY; $y < $endY; $y++){
                for($z = $startZ; $z < $endZ; $z++){
                    $randIndex = mt_rand(0, count($blockUpdates)-1);
                    if(isset($blockUpdatesKeys[$randIndex]) and isset($blockUpdates[$blockUpdatesKeys[$randIndex]]) and ($blockUpdates[$blockUpdatesKeys[$randIndex]] instanceof Block)){
                        $level->setBlock(new Vector3($x, $y, $z), $blockUpdates[$blockUpdatesKeys[$randIndex]]);
                    }
                    unset($blockUpdatesKeys[$randIndex]);
                    $blockUpdatesKeys = array_values($blockUpdatesKeys);
                }
            }
        }
        $this->api->chat->broadcast($this->CHAT_PREFIX . "Mine " . $mineName . " has been reset. ");
    }
    */
    
    public function handleSignsTap(&$data, $event){
        if(!($data["player"] instanceof Player)){return;}
        if($data["text"] != "[planet]"){return;}
        if(strtolower($data["data"]["Text2"]) == "enter"){
            $data["player"]->sendChat($this->CHAT_PREFIX . "Teleporting you to your own planet. ");
            if($this->planetExists(strtolower($data["player"]->username)) == false){
                $data["player"]->sendChat($this->CHAT_PREFIX . "Creating your planet... ");
                $this->api->file->copyFolder(FILE_PATH . $this->presetFolderName, FILE_PATH . "worlds/planet_" . strtolower($data["player"]->username));
            }
            if(!($this->api->level->get("planet_" . strtolower($data["player"]->username)) instanceof Level)){
                $this->api->level->loadLevel("planet_" . strtolower($data["player"]->username));
            }
            $data["player"]->teleport($this->api->level->get("planet_" . strtolower($data["player"]->username))->getSafeSpawn());
            return;
        }
        if(strtolower($data["data"]["Text2"]) == "delete"){
            $data["player"]->sendChat($this->CHAT_PREFIX . "Deleting your planet... ");
            if($this->planetExists($data["player"]->iusername)){
                if($this->api->level->get("planet_" . strtolower($data["player"]->username)) instanceof Level){
                    $this->api->level->unloadLevel($this->api->level->get("planet_" . strtolower($data["player"]->username)), true);
                }
                $this->api->file->deleteFolder(FILE_PATH . "worlds/planet_" . strtolower($data["player"]->username));
                $data["player"]->sendChat($this->CHAT_PREFIX . "Your planet has been deleted! ");
            }else{
                $data["player"]->sendChat($this->CHAT_PREFIX . "You don't have a planet... ");
            }
        }
    }
    
    public function handleSignsTextChange(&$data, $event){
        if($data["text"] != "[planet]"){return;}
        return(false); //No need to worry about signs creation now. 
        /*
        if($this->api->perm->checkPerm($data["username"], "pmess.prison.signs.create.*")){return(true);}
        $cmd = strtolower($data["data"]["Text2"]);
        switch($cmd){
            case "planet":
                if($this->api->perm->checkPerm($data["username"], "pmess.prison.signs.create.*")){return(true);}
                break;
            case ""
        }
        */
    }
    
    public function timerUnloadLevels(){
        foreach($this->api->level->getAll() as $lvName => $level){
            if($this->api->utils->startWith(strtolower($level->getName()), "planet_") == false){continue;}
            if(count($level->players) < 1){
                $this->api->level->unloadLevel($level, true);
            }
        }
    }
    
    /* ============================================================
                            Utility Functions
       ============================================================ */
    
    public function planetExists($username){
        if (!file_exists(FILE_PATH . "worlds/planet_" . strtolower($username)) and !is_dir(FILE_PATH . "worlds/planet_" . strtolower($username))) {
            return(false);
        }else{
            return(true);
        }
    }
    
    public function mineExists($mineName){
        if(isset($this->mines[strtolower($mineName)])){
            return(true);
        }else{
            return(false);
        }
    }
    
    public function checkValidPos($posData){
        if(!(is_array($posData))){return(false);}
        if(count($posData) != 3){return(false);}
        if($posData[0] != -1 and $posData[1] != -1 and $posData[2] != -1){
            return(true);
        }else{
            return(false);
        }
    }
    
    public function getHelp($page){
        $page = (int)$page;
        if($page < 1){$page = 1;}
        if($page > $this->maxHelpPages){$page = $this->maxHelpPages;}
        $ret = $this->CHAT_PREFIX . "[ Prison Mine Commands(" . $page . "/3) ]\n";
        switch($page){
            /*
            case 1:
                $ret .= "● /mine help [Page] - Show the help text\n● /mine pos1/pos2/tppos - Set the coordinates\n● /mine create [Name] - Create a mine\n● /mine set [Name] [BlockID] [Meta] [Chance] - Add a block type";
                break;
            case 2:
                $ret .= "● /mine unset [Name] [BlockID] [Meta] - Remove a block type\n● /mine reset [Name] - Manually reset a mine● /mine remove [Name] - Remove a mine\n";
                break;
            */
            default:
                //$ret .= $this->CHAT_PREFIX . "[X] Invalid help page number. ";
                $ret .= $this->CHAT_PREFIX . "● /mine reset - Reset the mine ";
                break;
        }
        return($ret);
    }
    
	public function saveConfig(){
        $cfg = new Config($this->api->plugin->configPath($this)."Mines.yml", CONFIG_YAML, array("Mines" => array()));
        $cfg->set("Mines", $this->mines);
        $cfg->save();
        unset($cfg);
	}
    
    public function __destruct(){
    }
}
?>
