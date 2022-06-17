<?php
/*
 author: Filip Brna, xbrnaf00
 date : 5.3.2021
 VUT FIT IPP 1.project
*/

/* TESTY SU NA .IPPCODE19!!*/


ini_set('display_errors','strerr');


## Kontrola ci bol zadany parameter --help 

if ($argc > 1){
    if ($argv[1] == "--help" && $argc == 2){      
        echo(" ---> script example: php7.4 parse.php < inputFile\n");
        exit(0);
    }
    else{  # $argv[1] == "--help" && $argc > 2
        #fwrite(STDERR,"error: 10\n");
        exit(10);
    }
}

echo('<?xml version="1.0" encoding="UTF-8"?>'."\n");
$correct_header = false;
$order_number = 0;
$empty_stdin = true;

while ($line = fgets(STDIN)){
    $empty_stdin = false;
    $line = preg_replace("/&/",'&amp;',$line);                       #nacitanie po riadku zo vstupu, reg. vyrazy na upravu riadku (odkomentovanie, zamenenie zanakov)
    $line = preg_replace("/</",'&lt;',$line);
    $line = preg_replace("/>/",'&gt;',$line);
    $line = preg_replace('/#.*/','',preg_replace('#//.*#','',preg_replace('#/\*(?:[^*]*(?:\*(?!/))*)*\*//#','',($line))));
    if ($correct_header == false && $line != "\n"){
        if ((strtoupper(trim($line, "\n")) == ".IPPCODE21") || (strtoupper(trim($line, "\n")) == ".IPPCODE21 ")) {      #kontrolovanie hlavicky
            $correct_header = true;
            echo('<program language="IPPcode21">'."\n");
        }
        else{
            #fwrite(STDERR,"error: 21\n");
            exit(21);
        }
    }
    else if ($line == "\n") {   #preskocenie prazdnych riadkov
        continue;
    }
    else{
        $order_number++;
        $splitted_input = array_values(array_filter(explode(' ',trim($line, "\n"))));   #vlozenie parametrov do pola
        $arguments_counter = count($splitted_input);
        if ($arguments_counter != 0){
            $opcode = strtoupper($splitted_input[0]);
        }
        else{
            $opcode = "";
        }

        if ($arguments_counter != 0){
            switch($opcode)
            {
                case 'MOVE': #⟨var⟩ ⟨symb⟩          
                case 'INT2CHAR': #⟨var⟩ ⟨symb⟩
                case 'STRLEN': #⟨var⟩ ⟨symb⟩
                case 'TYPE': #⟨var⟩ ⟨symb⟩
                case 'NOT': #⟨var⟩ ⟨symb⟩

                    # 1. kontrola poctu argumentov
                    # 2. kontorola syntaxe pomocou reg. vyrazov
                    # 3. vymazanie \000 - \999 

                    if ($arguments_counter == 3){
                        $first_argument_var = preg_match("/^(LF|TF|GF)@[[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*$/", $splitted_input[1]);
                        $second_argument_var = preg_match("/^(LF|TF|GF)@[[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*$/", $splitted_input[2]);
                        $tmp = preg_replace("/\\\\([0-9]{3})/","",$splitted_input[2]);

                        if ($first_argument_var == 1 && $second_argument_var == 1){
                            echo("\t<instruction order=\"$order_number\" opcode=\"$opcode\">\n");
                            echo("\t\t<arg1 type=\"var\">$splitted_input[1]</arg1>\n");
                            echo("\t\t<arg2 type=\"var\">$splitted_input[2]</arg2>\n");
                            echo("\t</instruction>\n");
                        }
                        elseif ($first_argument_var == 1 ) {
                            echo("\t<instruction order=\"$order_number\" opcode=\"$opcode\">\n");
                            echo("\t\t<arg1 type=\"var\">$splitted_input[1]</arg1>\n");                     # semanticka kontrola datovych typov
                            $int = preg_match("/^int@([(\+|\-|)(0-9)]+|counter$)/", $tmp);
                            $str = preg_match("/^string@([[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]|)/", $tmp);
                            $str_backslash = preg_match("/\\\\/", $tmp);
                            $bool = preg_match("/^bool@(true|false)$/", $tmp);
                            $nil = preg_match("/^nil@nil$/", $tmp);

                            if ( $int || ($str && !$str_backslash)  || $bool || $nil ) {
                                $value = explode('@',$splitted_input[2],2);
                                echo("\t\t<arg2 type=\"$value[0]\">$value[1]</arg2>\n");
                                echo("\t</instruction>\n");
                            }
                            else {
                                #fwrite(STDERR,"error: 23\n");
                                exit(23); #zla semantika argumentov
                            }
                        }
                        else{
                            #fwrite(STDERR,"error: 23\n");
                            exit(23); #zla semantika argumentov
                        }
                    }
                    else{
                        #fwrite(STDERR,"error: 23\n");
                        exit(23); #zly pocet argumentov
                    }
                    break;
                case 'CREATEFRAME':
                case 'PUSHFRAME':
                case 'POPFRAME':
                case 'RETURN':
                case 'BREAK':
                    if ($arguments_counter == 1){
                        echo("\t<instruction order=\"$order_number\" opcode=\"$opcode\"></instruction>\n");
                    }
                    else{
                        #fwrite(STDERR,"error: 23\n");
                        exit(23); #zly pocet argumentov
                    }
                    break;
                case 'POPS': #⟨var⟩
                case 'DEFVAR': #⟨var⟩
                    if ($arguments_counter == 2){
                        $first_argument_var = preg_match("/^(LF|TF|GF)@[[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*$/", $splitted_input[1]);
                        if ($first_argument_var == 1){
                            echo("\t<instruction order=\"$order_number\" opcode=\"$opcode\">\n");
                            echo("\t\t<arg1 type=\"var\">$splitted_input[1]</arg1>\n");
                            echo("\t</instruction>\n");
                        }
                        else{
                            #fwrite(STDERR,"error: 23\n");
                            exit(23); #zla semantika argumentov
                        }
                    }
                    else{
                        #fwrite(STDERR,"error: 23\n");
                        exit(23); #zly pocet argumentov
                    }
                    break;
                case 'CALL': #⟨label⟩
                case 'LABEL': #⟨label⟩
                case 'JUMP': #⟨label⟩
                    if ($arguments_counter == 2){
                        $first_argument_label = preg_match("/[@]/", $splitted_input[1]);
                        if ( $first_argument_label == 1){
                            #fwrite(STDERR,"error: 23\n");
                            exit (23); #zla semantika argumentov
                        }
                        echo("\t<instruction order=\"$order_number\" opcode=\"$opcode\">\n");
                        echo("\t\t<arg1 type=\"label\">$splitted_input[1]</arg1>\n");
                        echo("\t</instruction>\n");
                    }
                    else{
                        #fwrite(STDERR,"error: 23\n");
                        exit(23); #zly pocet argumentov
                    }
                    break;
                case 'PUSHS': #⟨symb⟩
                case 'WRITE': #⟨symb⟩
                case 'EXIT': #⟨symb⟩
                case 'DPRINT': #⟨symb⟩
                        
                    if ($arguments_counter == 2){
                        $first_argument_var = preg_match("/^(LF|TF|GF)@[[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*$/", $splitted_input[1]);       # kontorola syntaxe pomocou reg. vyrazov
                        $tmp = preg_replace("/\\\\([0-9]{3})/","",$splitted_input[1]);                      
                        if ($first_argument_var == 1){
                            echo("\t<instruction order=\"$order_number\" opcode=\"$opcode\">\n");
                            echo("\t\t<arg1 type=\"var\">$splitted_input[1]</arg1>\n");
                            echo("\t</instruction>\n");
                        }
                        else{
                            $int = preg_match("/^int@([(\+|\-|)(0-9)]+|counter$)/", $tmp);
                            $str = preg_match("/^string@([[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*|)/", $tmp);  # semanticka kontrola datovych typov
                            $str_backslash = preg_match("/\\\\/", $tmp);
                            $bool = preg_match("/^bool@(true|false)$/", $tmp);
                            $nil = preg_match("/^nil@nil$/", $tmp);

                            if ( $int || ($str && !$str_backslash)  || $bool || $nil ){
                                echo("\t<instruction order=\"$order_number\" opcode=\"$opcode\">\n");
                                $value = explode('@',$splitted_input[1],2);
                                echo("\t\t<arg1 type=\"$value[0]\">$value[1]</arg1>\n");
                                echo("\t</instruction>\n");
                            }
                            else{
                                #fwrite(STDERR,"error: 23\n");
                                exit(23); #zla semantika argumentov
                            }
                        }
                    }
                    else{
                        #fwrite(STDERR,"error: 23\n");
                        exit(23); #zly pocet argumentov
                    }
                    break;
                case 'ADD': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'SUB': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'MUL': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'IDIV': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'LT': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'GT': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'EQ': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'AND': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'OR': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'STRI2INT': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'CONCAT': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'GETCHAR': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'SETCHAR': #⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                    if ($arguments_counter == 4){

                        $first_argument_var = preg_match("/^(LF|TF|GF)@[[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*$/", $splitted_input[1]);   # kontorola syntaxe pomocou reg. vyrazov
                        $second_argument_var = preg_match("/^(LF|TF|GF)@[[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*$/", $splitted_input[2]);  
                        $third_argument_var = preg_match("/^(LF|TF|GF)@[[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*$/", $splitted_input[3]);
                        $tmp_first = preg_replace("/\\\\([0-9]{3})/","",$splitted_input[2]);
                        $tmp_second = preg_replace("/\\\\([0-9]{3})/","",$splitted_input[3]);

                        if ($first_argument_var == 1){ 
                            echo("\t<instruction order=\"$order_number\" opcode=\"$opcode\">\n");
                            echo("\t\t<arg1 type=\"var\">$splitted_input[1]</arg1>\n");

                            if ($second_argument_var == 1 && $third_argument_var == 1){
                                echo("\t\t<arg2 type=\"var\">$splitted_input[2]</arg2>\n");
                                echo("\t\t<arg3 type=\"var\">$splitted_input[3]</arg3>\n");
                                echo("\t</instruction>\n");
                            }
                            else{

                                $int_first = preg_match("/^int@([(\+|\-|)(0-9)]+|counter$)/", $tmp_first);                                  # semanticka kontrola datovych typov
                                $str_first = preg_match("/^string@([[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*|)/", $tmp_first);          
                                $str__backslash_first = preg_match("/\\\\/", $tmp_first);   
                                $bool_first = preg_match("/^bool@(true|false)$/", $tmp_first);
                                $nil_first = preg_match("/^nil@nil$/", $tmp_first);

                                $int_second = preg_match("/^int@([(\+|\-|)(0-9)]+|counter$)/", $tmp_second);
                                $str_second = preg_match("/^string@([[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]|)/", $tmp_second);         # semanticka kontrola datovych typov
                                $int_backslash_second = preg_match("/\\\\/", $tmp_second);
                                $bool_second = preg_match("/^bool@(true|false)$/", $tmp_second);
                                $nil_second = preg_match("/^nil@nil$/", $tmp_second);

                                if ($second_argument_var == 0 && $third_argument_var == 1 &&
                                   ($int_first || ($str_first && !$str__backslash_first)  || $bool_first || $nil_first) ){

                                    $value = explode('@',$splitted_input[2],2);
                                    echo("\t\t<arg2 type=\"$value[0]\">$value[1]</arg2>\n");
                                    echo("\t\t<arg3 type=\"var\">$splitted_input[3]</arg3>\n");
                                    echo("\t</instruction>\n");
                                }
                                elseif ($second_argument_var == 1 && $third_argument_var == 0 &&
                                       ($int_second || ($str_second && !$int_backslash_second)  || $bool_second || $nil_second) ){

                                    echo("\t\t<arg2 type=\"var\">$splitted_input[2]</arg2>\n");
                                    $value = explode('@',$splitted_input[3],2);
                                    echo("\t\t<arg3 type=\"$value[0]\">$value[1]</arg3>\n");
                                    echo("\t</instruction>\n");
                                }
                                elseif ($second_argument_var == 0 && $third_argument_var == 0 &&
                                       ($int_first || ($str_first && !$str__backslash_first)  || $bool_first || $nil_first) &&
                                       ($int_second || ($str_second && !$int_backslash_second)  || $bool_second || $nil_second) ){

                                    $value = explode('@',$splitted_input[2],2);
                                    echo("\t\t<arg2 type=\"$value[0]\">$value[1]</arg2>\n");
                                    $value = explode('@',$splitted_input[3],2);
                                    echo("\t\t<arg3 type=\"$value[0]\">$value[1]</arg3>\n");
                                    echo("\t</instruction>\n");
                                }
                                else{
                                    #fwrite(STDERR,"error: 23\n");
                                    exit (23); #zla semantika argumentov
                                }
                            }
                        }
                        else{
                            #fwrite(STDERR,"error: 23\n");
                            exit(23); #zla semantika argumentov
                        }
                    }
                    else{
                        #fwrite(STDERR,"error: 23\n");
                        exit(23); #zly pocet argumentov
                    }
                    break;
                case 'JUMPIFEQ': #⟨label⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                case 'JUMPIFNEQ': #⟨label⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
                    if ($arguments_counter == 4){
                        $first_argument_label = preg_match("/[@]/", $splitted_input[1]);
                        if ($first_argument_label == 1){
                            #fwrite(STDERR,"error: 23\n");
                            exit (23); #zla semantika argumentov
                        }
                        $second_argument_var = preg_match("/^(LF|TF|GF)@[[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*$/", $splitted_input[2]);      # kontorola syntaxe pomocou reg. vyrazov
                        $third_argument_var = preg_match("/^(LF|TF|GF)@[[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*$/", $splitted_input[3]);
                        $tmp_first = preg_replace("/\\\\([0-9]{3})/","",$splitted_input[2]);
                        $tmp_second = preg_replace("/\\\\([0-9]{3})/","",$splitted_input[3]);
                        
                        echo("\t<instruction order=\"$order_number\" opcode=\"$opcode\">\n");
                        echo("\t\t<arg1 type=\"label\">$splitted_input[1]</arg1>\n");

                        if ($second_argument_var == 1 && $third_argument_var == 1){
                            echo("\t\t<arg2 type=\"var\">$splitted_input[2]</arg2>\n");
                            echo("\t\t<arg3 type=\"var\">$splitted_input[3]</arg3>\n");
                            echo("\t</instruction>\n");
                        }
                        else{

                            $int_first = preg_match("/^int@([(\+|\-|)(0-9)]+|counter$)/", $tmp_first);                                      # semanticka kontrola datovych typov
                            $str_first = preg_match("/^string@([[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*|)/", $tmp_first);
                            $str__backslash_first = preg_match("/\\\\/", $tmp_first);
                            $bool_first = preg_match("/^bool@(true|false)$/", $tmp_first);
                            $nil_first = preg_match("/^nil@nil$/", $tmp_first);

                            $int_second = preg_match("/^int@([(\+|\-|)(0-9)]+|counter$)/", $tmp_second);
                            $str_second = preg_match("/^string@([[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*|)/", $tmp_second);            # semanticka kontrola datovych typov
                            $int_backslash_second = preg_match("/\\\\/", $tmp_second);
                            $bool_second = preg_match("/^bool@(true|false)$/", $tmp_second);
                            $nil_second = preg_match("/^nil@nil$/", $tmp_second);

                            if ($second_argument_var == 0 && $third_argument_var == 1 &&
                               ($int_first || ($str_first && !$str__backslash_first)  || $bool_first || $nil_first) ){

                                $value = explode('@',$splitted_input[2],2);
                                echo("\t\t<arg2 type=\"$value[0]\">$value[1]</arg2>\n");
                                echo("\t\t<arg3 type=\"var\">$splitted_input[3]</arg3>\n");
                                echo("\t</instruction>\n");
                            }
                            elseif ($second_argument_var == 1 && $third_argument_var == 0 &&
                                   ($int_second || ($str_second && !$int_backslash_second)  || $bool_second || $nil_second) ){

                                echo("\t\t<arg2 type=\"var\">$splitted_input[2]</arg2>\n");
                                $value = explode('@',$splitted_input[3],2);
                                echo("\t\t<arg3 type=\"$value[0]\">$value[1]</arg3>\n");
                                echo("\t</instruction>\n");
                            }
                            elseif ($second_argument_var == 0 && $third_argument_var == 0 &&
                                   ($int_first || ($str_first && !$str__backslash_first)  || $bool_first || $nil_first) &&
                                   ($int_second || ($str_second && !$int_backslash_second)  || $bool_second || $nil_second) ){

                                $value = explode('@',$splitted_input[2],2);
                                echo("\t\t<arg2 type=\"$value[0]\">$value[1]</arg2>\n");
                                $value = explode('@',$splitted_input[3],2);
                                echo("\t\t<arg3 type=\"$value[0]\">$value[1]</arg3>\n");
                                echo("\t</instruction>\n");
                            }
                            else{
                                #fwrite(STDERR,"error: 23\n");
                                exit (23); #zla semantika argumentov
                            }
                        }    
                    }
                    else{
                        #fwrite(STDERR,"error: 23\n");
                        exit(23); #zly pocet argumentov
                    }
                    break;
                case 'READ': #⟨var⟩ ⟨type⟩
                    if ($arguments_counter == 3){
                        $first_argument_var = preg_match("/^(LF|TF|GF)@[[:alpha:]_\-\$&%!?\*][[:alnum:]_\-\$&%!?\*]*$/", $splitted_input[1]);   # kontorola syntaxe pomocou reg. vyrazov
                        $second_argument_type = preg_match("/^(int|string|bool|nil)$/", $splitted_input[2]);                  # semanticka kontrola datovych typov
                        if ($first_argument_var == 1 && $second_argument_type == 1 ){
                            echo("\t<instruction order=\"$order_number\" opcode=\"$opcode\">\n");
                            echo("\t\t<arg1 type=\"var\">$splitted_input[1]</arg1>\n");
                            echo("\t\t<arg2 type=\"type\">$splitted_input[2]</arg2>\n");
                            echo("\t</instruction>\n");
                        }
                        else{
                            #fwrite(STDERR,"error: 23\n");
                            exit(23); #zla semantika argumentov
                        }
                    }
                    else{
                        #fwrite(STDERR,"error: 23\n");
                        exit(23); #zly pocet argumentov
                    }
                    break;
                case '.IPPCODE21':
                    #fwrite(STDERR,"error: 23\n");
                    exit(23);
                    break;
                default:
                    #fwrite(STDERR,"error: 22\n");
                    exit(22);
                    break;
            }
        }
    }
}
if ($empty_stdin == true){
    #fwrite(STDERR,"error: 21\n");
    exit(21);       # prazdny stdin
}


echo('</program>'."\n");
exit(0);            # ukoncenie programu
?>
