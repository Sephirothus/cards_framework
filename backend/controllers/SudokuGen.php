<?php

class SudokuGen {

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function get() {
        return $this->_gen();
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    private function _gen() {
        $temp = [[1,4,7],[2,5,8],[3,6,9]];
        $data = [];
        echo "<br><br><br>";
        foreach ($temp as $arr) {
            foreach ($arr as $val) {
                $haha = $this->_genBaseCycle($val);
                $data[] = $haha;
                echo implode('|', $haha)."<br>";
            }
        }
        $this->_change($data, 20);
        $this->_deleteCells($data);
        echo "<br>Result:<br>===================<br>";
        foreach ($data as $arr) {
            echo implode('|', $arr)."<br>";
        }
        /*echo "<br><pre>";
        print_r($data);
        echo "</pre>";*/
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    private function _change(&$data, $num) {
        $funcs = ['_changePlaces', '_changeRegions', '_transposing'];
        for ($i=0;$i < $num; $i++) {
            $method = $funcs[array_rand($funcs)];
            $this->$method($data);
            /*foreach ($data as $arr) {
                echo implode('|', $arr)."<br>";
            }*/
        }
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    private function _deleteCells(&$data, $num=0) {
        $watched = [];
        for ($i=0; $i<35;$i++) {
            while (in_array(($cell = rand(1,81)), $watched));
            $watched[] = $cell;
            $el = floor($cell/9);
            if ($el > 0) $data[$el][$cell%9] = 0;
            else $data[0][$cell%9] = 0;
        }
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    private function _check($data) {
        $vert = [];
        $n = 0;
        foreach ($data as $key => $arr) {
            $horiz = [];
            for ($i=0;$i<count($arr);$i++) {
                if (!in_array($arr[$i], $horiz)) $horiz[] = $arr[$i];
                else return false;
                if (!in_array($data[$i][$n], $vert)) $vert[] = $data[$i][$n];
                else return false;
            }
            $n++;
            if ($n >= count($data)) break;
        }
        return true;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    private function _genBaseCycle($num) {
        $temp = [];
        $first = $num;
        while ($num <= 9) {
            $temp[] = $num++;
        }
        if (count($temp) < 9) {
            $num = 1;
            while ($num < $first) {
                $temp[] = $num++;
            }
        }
        return $temp;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    private function _changePlaces(&$data) {
        $place = ['col','row'];
        $region = rand(0,2);
        $rowCol1 = rand(0,2);
        while (in_array(($rowCol2 = rand(0,2)), array($rowCol1)));
        $prev = $region*3;
        $place = $place[array_rand($place)];
        switch ($place) {
            case 'col':
                foreach ($data as $key => $arr) {
                    $temp = $data[$key][$prev+$rowCol1];
                    $data[$key][$prev+$rowCol1] = $data[$key][$prev+$rowCol2];
                    $data[$key][$prev+$rowCol2] = $temp;
                }
                break;
            case 'row':
                $temp = $data[$prev+$rowCol1];
                $data[$prev+$rowCol1] = $data[$prev+$rowCol2];
                $data[$prev+$rowCol2] = $temp;
                break;
        }
        echo '<br>method: changePlaces; place: '.$place.'; region: '.$region.'; row: '.$rowCol1.'; row: '.$rowCol2.'<br>================<br>';
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    private function _changeRegions(&$data) {
        $place = ['horiz','vert'];
        $region1 = rand(0,2);
        while (in_array(($region2 = rand(0,2)), array($region1)));
        $place = $place[array_rand($place)];
        switch ($place) {
            case 'vert':
                foreach ($data as $key => $arr) {
                    for ($i=0;$i<3;$i++) {
                        $temp = $data[$key][$i+$region1*3];
                        $data[$key][$i+$region1*3] = $data[$key][$i+$region2*3];
                        $data[$key][$i+$region2*3] = $temp;
                    }
                }
                break;
            case 'horiz':
                for ($i=0;$i<3;$i++) {
                    $temp = $data[$i+$region1*3];
                    $data[$i+$region1*3] = $data[$i+$region2*3];
                    $data[$i+$region2*3] = $temp;
                }
                break;
        }
        echo '<br>method: changeRegions; place: '.$place.'; region: '.$region1.'; region: '.$region2.';<br>================<br>';
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    private function _transposing(&$data) {
        $table = [];
        for ($i=0;$i<count($data);$i++) {
            $temp = [];
            foreach ($data as $key => $arr) {
                $temp[] = $data[$key][$i];
            }
            $table[] = $temp;
        }
        $data = $table;
        echo '<br>method: transposing;<br>================<br>';
    }
}