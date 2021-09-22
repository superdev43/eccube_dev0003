/*
 * Copyright (c) 2018 VeriTrans Inc., a Digital Garage company. All rights reserved.
 * http://www.veritrans.co.jp/
 */

// 名前空間を設定
window.VeriTrans4G = window.VeriTrans4G || {};

// MDKトークン取得用 APIリクエスト
VeriTrans4G.fetchMdkToken = function() {
    VeriTrans4G.resetError();

    // 入力チェック
    if (!VeriTrans4G.validate()) {
        VeriTrans4G.isProcessing = false;
        return false;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', VeriTrans4G.tokenApiUrl, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('Content-Type', 'application/json; charset=utf-8');
    xhr.onloadend = function() {
        VeriTrans4G.handleFetchMdkToken(JSON.parse(xhr.response));
    };
    // 通信エラー
    xhr.ontimeout = function() {
        VeriTrans4G.isProcessing = false;
        alert("通信エラーが発生しました。(timeout)");
        return false;
    };
    xhr.onabort = function() {
        VeriTrans4G.isProcessing = false;
        alert("通信エラーが発生しました。(abort)");
        return false;
    };

    xhr.send(VeriTrans4G.getRequestParams());
};

// APIリクエスト用パラメータを生成
VeriTrans4G.getRequestParams = function() {
    var cardNumber   = document.querySelector('input[name="payment_credit[card_no]"]').value;
    var expireMonth  = document.querySelector('select[name="payment_credit[expiry_month]"]').value;
    var expireYear   = document.querySelector('select[name="payment_credit[expiry_year]"]').value;

    var params = {
        token_api_key: VeriTrans4G.tokenApiKey,
        card_number: cardNumber,
        card_expire: expireMonth+'/'+expireYear,
        lang: 'ja'
    };
    if (VeriTrans4G.securityFlg) {
        var securityCode = document.querySelector('input[name="payment_credit[security_code]"]').value;
        params.security_code = securityCode;
    }

    return JSON.stringify(params);
};

// MDKトークン取得成功時
VeriTrans4G.handleFetchMdkToken = function(response) {
    if (response.status === 'success') {
        const formElm = document.querySelector('#vt4g_form_credit');
        formElm.querySelector('input[name="token_id"]').value = response.token;
        formElm.querySelector('input[name="token_expire_date"]').value=response.token_expire_date;

        // 入力内容のクリア
        VeriTrans4G.resetForm();

        // フォームを送信
        formElm.submit();
        // EC-CUBE側で定義されているオーバーレイ表示 実行
        window.loadingOverlay();
    } else {
        // エラーメッセージ表示
        VeriTrans4G.setError('【'+response.code+'】'+response.message);
    }
};

// エラーメッセージを表示
VeriTrans4G.setError = function(message) {
    document.querySelector('#vt4g_form_credit_error').innerHTML = message;
};

// エラーメッセージをクリア
VeriTrans4G.resetError = function() {
    VeriTrans4G.setError('');
};

// 入力されているクレジットカード情報をクリア
VeriTrans4G.resetForm = function() {
    var fields = Array.prototype.slice.call(document.querySelectorAll('[name^="payment_credit"]'),0);

    fields.forEach(function(field) {
        if (field.name != 'payment_credit[_token]' && field.name != 'payment_credit[payment_type]' && field.name != 'payment_credit[cardinfo_regist]') {
            field.value = /select/i.test(field.nodeName)
                ? field.querySelector('option').getAttribute('value')
                : '';
            VeriTrans4G.hideErrorMessage(field);
        }
    });
};

// 必須チェック
VeriTrans4G.validateNotBlank = function(key, name, message, formKey) {
    if (!formKey) {
        formKey = 'payment_credit';
    }

    var field = document.querySelector('[name="'+formKey+'['+key+']"]');
    var value = field.value;
    var control = /select/i.test(field.nodeName)
        ? '選択'
        : '入力';

    if (value == '' || value == null) {
        VeriTrans4G.showErrorMessage(field, (message || '※ '+name+'が'+control+'されていません。'));
        return false;
    }

    return true;
};

// 正規表現チェック
VeriTrans4G.validateRegex = function(key, name, regex, message) {
    var field = document.querySelector('[name="payment_credit['+key+']"]');
    var value = field.value;

    if (new RegExp(regex).test(value)) {
        VeriTrans4G.showErrorMessage(field, (message || '※ '+name+'の入力書式が不正です。'));
        return false;
    }

    return true;
};

// 文字数チェック
VeriTrans4G.validateRange = function(key, name, min, max, message) {
    var field = document.querySelector('[name="payment_credit['+key+']"]');
    var value = field.value;

    var isValid = true;

    if (min != null) {
        isValid = min <= value.length;
    }
    if (isValid && max != null) {
        isValid = value.length <= max;
    }

    var defaultMessage = (min != null && max != null && min === max)
        ? '※ '+name+'は'+min+'桁で入力してください。'
        : '※ '+name+'が'+(min != null ? min+'桁' : '')+'〜'+(max != null ? max+'桁' : '')+'の範囲ではありません。';

    if (!isValid) {
        VeriTrans4G.showErrorMessage(field, (message || defaultMessage));
        return false;
    }

    return true;
};

// カード番号入力チェック
VeriTrans4G.validateCardNumber = function() {
    var key = 'card_no';
    var name = 'クレジットカード番号';

    var field = document.querySelector('input[name="payment_credit['+key+']"]');
    var value = field.value;
    var minLength = field.getAttribute('minlength');
    var maxLength = field.getAttribute('maxlength');

    VeriTrans4G.hideErrorMessage(field);

    // 必須チェック & 形式チェック & 桁数チェック
    return VeriTrans4G.validateNotBlank(key, name) &&
        VeriTrans4G.validateRegex(key, name, '[^0-9]', '※ '+name+'に数字以外の文字が含まれています。') &&
        VeriTrans4G.validateRange(key, name, minLength, maxLength);
};

// カード有効期限チェック
VeriTrans4G.validateExpiration = function() {
    var validLength = 2;
    var monthKey = 'expiry_month';
    var monthName = 'カード有効期限(月)';
    var yearKey = 'expiry_year';
    var yearName = 'カード有効期限(年)';

    var monthField = document.querySelector('select[name="payment_credit['+monthKey+']"]');
    var yearField = document.querySelector('select[name="payment_credit['+yearKey+']"]');

    VeriTrans4G.hideErrorMessage(monthField);
    VeriTrans4G.hideErrorMessage(yearField);

    // 必須チェック & 形式チェック & 桁数チェック
    var isValidMonth = VeriTrans4G.validateNotBlank(monthKey, monthName) &&
        VeriTrans4G.validateRegex(monthKey, monthName, '[^0-9]', '※ '+monthName+'に数字以外の文字が含まれています。') &&
        VeriTrans4G.validateRange(monthKey, monthName, validLength, validLength);

    var isValidYear = VeriTrans4G.validateNotBlank(yearKey, yearName) &&
        VeriTrans4G.validateRegex(yearKey, yearName, '[^0-9]', '※ '+yearName+'に数字以外の文字が含まれています。') &&
        VeriTrans4G.validateRange(yearKey, yearName, validLength, validLength);

    return isValidMonth && isValidYear && VeriTrans4G.validateExpirationDate(yearField, monthField);
};

// カード有効期限 日付チェック
VeriTrans4G.validateExpirationDate = function(yearField, monthField) {
    // フォームの入力値は西暦の末尾2桁のため先頭2桁と結合
    var year = '20'+yearField.value;
    // Date関数で1月が'0'から始まるため-1
    var month = monthField.value - 1;
    // 日付の入力はないため1日とする
    var day = '01';

    var date = new Date(year, month, day);
    if (!(date.getFullYear() == year && date.getMonth() == month && date.getDate() == day)) {
        VeriTrans4G.showErrorMessage(yearField, '※ 不正な年月です。');
        return false;
    }

    return true;
};

// カード名義人名チェック
VeriTrans4G.validateOwner = function() {
    var lastKey = 'last_name';
    var lastName = 'カード名義人名(姓)';
    var firstKey = 'first_name';
    var firstName = 'カード名義人名(名)';

    var lastField = document.querySelector('input[name="payment_credit['+lastKey+']"]');
    var firstField = document.querySelector('input[name="payment_credit['+firstKey+']"]');

    VeriTrans4G.hideErrorMessage(lastField);
    VeriTrans4G.hideErrorMessage(firstField);

    // 必須チェック & 形式チェック
    var isValidLastName = VeriTrans4G.validateNotBlank(lastKey, lastName) &&
        VeriTrans4G.validateRegex(lastKey, lastName, '[^a-zA-Z]', '※ '+lastName+'は半角英字で入力してください');

    var isValidFirstName = VeriTrans4G.validateNotBlank(firstKey, firstName) &&
        VeriTrans4G.validateRegex(firstKey, firstName, '[^a-zA-Z]', '※ '+firstName+'は半角英字で入力してください');

    return isValidLastName && isValidFirstName;
};

// 支払い方法チェック
VeriTrans4G.validatePaymentType = function() {
    var key = 'payment_type';
    var name = 'お支払い方法';

    var field = document.querySelector('select[name="payment_credit['+key+']"]');
    var value = field.value;

    VeriTrans4G.hideErrorMessage(field);

    // 必須チェック
    return VeriTrans4G.validateNotBlank(key, name);
};

// セキュリティコードチェック
VeriTrans4G.validateSecurityCode = function() {
    var key = 'security_code';
    var name = 'セキュリティコード';

    var field = document.querySelector('input[name="payment_credit['+key+']"]');
    var value = field.value;
    var minLength = field.getAttribute('minlength');
    var maxLength = field.getAttribute('maxLength');

    VeriTrans4G.hideErrorMessage(field);

    // 必須チェック & 形式チェック & 桁数チェック
    return VeriTrans4G.validateNotBlank(key, name) &&
        VeriTrans4G.validateRegex(key, name, '[^0-9]', '※ '+name+'に数字以外の文字が含まれています。') &&
        VeriTrans4G.validateRange(key, name, minLength, maxLength);
};

// カード情報登録チェック
VeriTrans4G.validateCardinfoRegist = function() {
    var key = 'cardinfo_regist';
    var fields = document.querySelectorAll('input[name="payment_credit['+key+']"]');
    if (fields.length == 0) {
        return true;
    }
    var field = fields[0];
    var flg = false;

    for(var i = 0; i < fields.length; i++) {
        if(fields[i].checked) {
            flg = true;
        }
    }

    $(field).parent().parent().removeClass('error');
    $(field).parent().siblings('.ec-errorMessage').remove();
    if(!flg) {
        $(field).parent().parent().addClass('error').append('<p class="ec-errorMessage">※ カード情報登録が選択されていません。</p>');
    }
    return flg;
};

// 入力チェック
VeriTrans4G.validate = function() {
    var isValid = true;

    // クレジットカード番号チェック
    if (!VeriTrans4G.validateCardNumber()) {
        isValid = false;
    }

    // カード有効期限チェック
    if (!VeriTrans4G.validateExpiration()) {
        isValid = false;
    }

    // カード名義人名チェック
    if (!VeriTrans4G.validateOwner()) {
        isValid = false;
    }

    // お支払い方法チェック
    if (!VeriTrans4G.validatePaymentType()) {
        isValid = false;
    }

    // セキュリティコードチェック(セキュリティコード認証が有効な場合のみ)
    if (VeriTrans4G.securityFlg && !VeriTrans4G.validateSecurityCode()) {
        isValid = false;
    }

    // カード情報登録チェック
    if (!VeriTrans4G.validateCardinfoRegist()) {
        isValid = false;
    }

    return isValid;
};

// エラーメッセージ表示
VeriTrans4G.showErrorMessage = function(field, message) {
    $(field).parent().addClass('error').append('<p class="ec-errorMessage">'+message+'</p>');
};

// エラーメッセージ非表示
VeriTrans4G.hideErrorMessage = function(field) {
    $(field).parent().removeClass('error');
    $(field).siblings('.ec-errorMessage').remove();
};
