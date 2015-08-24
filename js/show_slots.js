(function ($) {
    "use strict";

    function createRow() {
        var $row = $("<tr>"),
            $td1 = $("<td>"),
            $td2 = $("<td>"),
            $td3 = $("<td>"),
            $td4 = $("<td>"),
            $td5 = $("<td>");

        var $removeBtn = $("<button>").attr({ "class" : "button remove" }).text("x");
        var $addNewBtn = $("<button>").attr({ "class" : "button add-new" }).text("Add slot");
        var $inputTxtDay = $("<select>").attr({ "name" : "slot-Day[]" });
        var $inputTxtFrom = $("<input>").attr({ "class" : "time start", "type" : "text", "name" : "slot-From[]" });
        var $inputTxtTo = $("<input>").attr({ "class" : "time end", "type" : "text", "name" : "slot-To[]" });

        var daysOfWeek = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
        $.each(daysOfWeek, function (i, day) {
            $inputTxtDay.append($("<option>").attr({ "value" : day }).text(day))
        });

        $removeBtn.click(function () {
            if ($(this).closest("table").find("tbody > tr").length > 1) {
                $(this).closest("tr").remove();
            }
        });

        $addNewBtn.click(function (e) {
            e.preventDefault();
            $(this).closest("tbody").append(createRow());
        });

        $inputTxtFrom.timepicker({
            "showDuration": true,
            "timeFormat": 'g:ia'
        });

        $inputTxtTo.timepicker({
            "showDuration": true,
            "timeFormat": 'g:ia'
        });

        $row.append($td1.append($removeBtn));
        $row.append($td2.append($inputTxtDay));
        $row.append($td3.append($inputTxtFrom));
        $row.append($td4.append($inputTxtTo));
        $row.append($td5.append($addNewBtn));

        $row.datepair();
        return $row;
    }

    $(".show_slots").each(function () {
        var $container = $(this);

        $container.find("button.add-new").click(function () {
            $container.find("tbody").append(createRow());
        });
    });

    if (typeof window.jsonSlots !== 'undefined' && window.jsonSlots.length > 0) {
        $.each(window.jsonSlots, function (i, slot) {
            var $row = createRow();
            $row.find("td:nth-child(2) select").attr("value", slot.day);
            $row.find("td:nth-child(3) input").attr("value", slot.from);
            $row.find("td:nth-child(4) input").attr("value", slot.to);
            $(".show_slots").find("tbody").prepend($row);
        });
    }
    else {
        $(".show_slots").find("tbody").append(createRow());
    }

})(jQuery);
