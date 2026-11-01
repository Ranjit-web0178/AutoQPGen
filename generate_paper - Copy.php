<?php
include 'config.php';

function getQuestionsFromAI($subject, $marks){
    $apiKey = GEMINI_API_KEY;
    $prompt = "Generate $marks marks question paper for $subject for BCA Sem 6. 5 Q of 2 marks, 3 Q of 5 marks, 2 Q of 10 marks.";
    $data = ["contents" => [["parts" => [["text" => $prompt]]]]];
    $model = "gemini-3.8-flash";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','x-goog-api-key: '.$apiKey]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    
    $res = json_decode($response, true);
    if(isset($res['candidates'][0]['content']['parts'][0]['text'])){
        return $res['candidates'][0]['content']['parts'][0]['text'];
    }
    return "API ERROR: ".$response;
}

if(isset($_POST['generate'])){
    $subject = $_POST['subject'];
    $marks = $_POST['marks'];
    $q = getQuestionsFromAI($subject, $marks);
    echo "<pre>$q</pre>";
}
?>
<form method="POST">
Subject: <input name="subject" value="DBMS" required><br><br>
Marks: <input name="marks" value="30" required><br><br>
<button type="submit" name="generate">Generate & Save</button>
</form>