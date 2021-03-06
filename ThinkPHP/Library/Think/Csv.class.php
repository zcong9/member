<?php

namespace Think;

class Csv {
    //导出csv文件
    public function put_csv($file_name,$list,$title) {
        //$file_name = "exam".time().".csv";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.$file_name );
        header('Cache-Control: max-age=0');
        $file  = fopen('php://output',"a");
        $limit = 1000;
        $calc  = 0;
        $tit   = [];
        foreach ($title as $v){
            $tit[] = iconv('UTF-8', 'GB2312//IGNORE',$v);
        }
        fputcsv($file,$tit);
        $tarr  = [];
        foreach ($list as $v){
            $calc++;
            if($limit == $calc){
                ob_flush();
                flush();
                $calc = 0;
            }
            foreach($v as $t){
                // 替换掉换行
                $t=preg_replace('/\s*/', '', $t);
                // 解决导出的数字会显示成科学计数法的问题
                $t=str_replace(',', "\t,", $t);
                $tarr[] = iconv('UTF-8', 'GB2312//IGNORE',$t);
            }
            fputcsv($file,$tarr);
            unset($tarr);
        }
        unset($list);
        fclose($file);
        exit();
    }

    /**
     * csv导入,此格式每次最多可以处理1000条数据
     * @param $file csv文件句柄
     * @param $line 读取行数 默认读取全部
     * @param $offset 从第几行开始读取 默认从第一行
     * @return array
     **/
    public function input_csv($file,$line,$offset) {
        $i = 0;

        while($data = fgetcsv($file,1000)) {

            if ($i < $offset && $offset) {
                $i++;
                continue;
            }

            if ($i > $line && $line) {
                break;
            }

            $i++;

            foreach ($data as $key=>$value) {
                $data[$key] = iconv("gbk","utf-8//IGNORE",$value);//转化编码
            }

            yield $data;   // 迭代

        }
    }
}
