//global variable used to store the users token
var token = "";

function displayTable() {
    $("#first").hide();
    fillOutTableOne();
    fillOutTableTwo();
	generateButton();
    $("#button").show();
    $("#second").show();
}

function fillOutTableOne() {
	$("#count").empty()
    $.ajax({
        type: "GET",
        contentType: 'application/json',
        url: "rest.php/v1/itemsSummary/" + token,
        success: function (data) {
			if(data.status == "OK"){
				var trHTML = '';
				$.each(data.items, function(i, item){
					trHTML += '<tr><td>' + item.item + '</td><td>' + item.count + '</td></tr>';
				});
				$("#count").append(trHTML);
			}
			else{
				alert("Failed to fill out 1st table \nReturned message: " + data.msg);
			}
	},
	error: function(data){
		console.log(data.status + " " + data.msg + " " + data.items);
	}
    });
}

function fillOutTableTwo() {
	$("#time").empty();
    $.ajax({
        type: "GET",
        contentType: 'application/json',
        url: "rest.php/items/" + token,
        success: function (data) {
			if(data.status == "OK"){
				var trHTML = '';
				$.each(data.items, function(i, item){
					trHTML += '<tr><td>' + item.item + '</td><td>' + item.timestamp + '</td></tr>';
				});
				$("#time").append(trHTML);
			} else{
				alert("Failed to fill out 2nd table \nReturned message: " + data.msg)
			}

	}
    })

}

function generateButton() {
    $.ajax({
        type: "GET",
        contentType: 'application/json',
        url: "rest.php/v1/items",
        success: function (data) {
			if(data.status == "OK"){
				var tHTML = "";
				$.each(data.items, function(i, item){
					tHTML += "<button class=\"itemButton\" pk=" + item.pk + " onclick='recordItem(this)'>" + item.item + "</button>";
				});
				$("#button").append(tHTML);
			} else{
				alert("Failed to generate buttons \nReturned message: " + data.msg);
			}

	}
    });
}

function recordItem(whichButton) {
	var itemData = {itemFK: $(whichButton).attr('pk'), token: token};
    $.ajax({
        type: "POST",
	data: JSON.stringify(itemData),
        contentType: 'application/json',
        url: "rest.php/v1/items",
        success: function (data) {
            if(data.status == "OK"){
				fillOutTableOne();
				fillOutTableTwo();
			}
        }
    })
}

$(document).ready(function () {
	// on form submition do this
	$("#login").submit(function(e){
		// retrieve form information
		var userInfo = {user: $("#u").val(), password: $("#p").val()};
		// make a POST request to the api
		$.ajax({
			type:"POST",
			url: "rest.php/v1/user",
			contentType: 'application/json',
			data: JSON.stringify(userInfo),
			success: function(data){
				// If the request is successful and returns a status of OK then move on to rendering tables
				if(data.status == "OK"){
					token = data.token;
					displayTable();
				}
				else{
					alert("Invalid login information \nReturned message: " + data.msg);
				}
			},
			error: function(data){
				alert(data.status);
			}

		});
	});
})
