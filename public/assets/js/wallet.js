(function () {
    const csrf = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");
    const cfg = window.WALLET_PAGE;
    if (!cfg || !cfg.routes) {
        console.error("WALLET_PAGE not bootstrapped");
        return;
    }

    const swalToast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 2200,
        timerProgressBar: true,
    });

    function fmt(n) {
        const num = parseFloat(n || 0);
        return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function userCurrency() {
        return cfg.currency; // comes from countries table via Blade
    }

    // ===== DataTable =====
    let table = $("#txTable").DataTable({
        processing: true,
        serverSide: true,
        paging: true,
        searching: false,
        lengthChange: false,
        ajax: function (data, callback) {
            const params = {
                type: $("#fType").val() || "",
                status: $("#fStatus").val() || "",
                from: $("#fFrom").val() || "",
                to: $("#fTo").val() || "",
            };
            $.getJSON(cfg.routes.txJson, params, function (res) {
                const rows = (res.data || []).map((row) => {
                    return [
                        row.id,
                        row.type,
                        (row.currency_symbol || "") +
                            " " +
                            Number(row.amount).toFixed(2),
                        row.status,
                        row.gateway || "-",
                        row.gateway_ref || row.reference || "-",
                        row.created_at,
                    ];
                });
                callback({
                    data: rows,
                    recordsTotal: res.total || rows.length,
                    recordsFiltered: res.total || rows.length,
                });
            }).fail(function () {
                callback({ data: [], recordsTotal: 0, recordsFiltered: 0 });
                Swal.fire({
                    icon: "error",
                    title: "Could not load transactions",
                });
            });
        },
    });

    $("#btnApply").on("click", () => table.ajax.reload());

    // ===== NEW: Payout Accounts (CRUD) =====
    const $list = $("#payoutList");
    const $modalPayout = $("#modalPayout");
    const $form = $("#formPayout");
    const $type = $("#payoutType");

    function togglePayoutFields() {
        const t = $type.val();
        $("#bankFields").toggleClass("d-none", t !== "bank");
        $("#upiFields").toggleClass("d-none", t !== "upi");
        $("#paypalFields").toggleClass("d-none", t !== "paypal");
    }
    $type.on("change", togglePayoutFields);

    function loadPayoutAccounts() {
        if (!$list.length) return;
        $list.empty().append('<div class="text-muted">Loading...</div>');
        $.getJSON(cfg.routes.payoutAccounts, function (res) {
            $list.empty();
            (res.data || []).forEach((a) => {
                const badge = a.is_default
                    ? '<span class="badge badge-success ml-2">Default</span>'
                    : "";
                const secondary =
                    a.type === "paypal"
                        ? a.paypal_email
                        : a.type === "upi"
                        ? a.upi_vpa
                        : `${a.ifsc || ""}`;
                const line = `
        <div class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <div><b>${a.type.toUpperCase()}</b> • ${
                    a.holder_name
                } ${badge}</div>
            <div class="small text-muted">${
                a.type === "bank" ? a.masked_account || "" : ""
            } ${secondary ? "• " + secondary : ""}</div>
          </div>
          <div>
            <button class="btn btn-sm btn-outline-primary mr-1 act-edit" data-id="${
                a.id
            }">Edit</button>
            <button class="btn btn-sm btn-outline-danger mr-1 act-del" data-id="${
                a.id
            }">Delete</button>
            ${
                a.is_default
                    ? ""
                    : `<button class="btn btn-sm btn-primary act-default" data-id="${a.id}">Make default</button>`
            }
          </div>
        </div>`;
                $list.append(line);
            });
            if (!res.data || !res.data.length) {
                $list.append(
                    '<div class="text-muted">No payout accounts saved yet.</div>'
                );
            }
        }).fail(() =>
            $list.html(
                '<div class="text-danger">Failed to load payout accounts</div>'
            )
        );
    }

    $("#btnAddPayout").on("click", function () {
        $form[0].reset();
        $("#payoutId").val("");
        $("#isDefault").prop("checked", false);
        $type.val("bank");
        togglePayoutFields();
        $modalPayout.modal("show");
    });

    $list.on("click", ".act-edit", function () {
        const id = $(this).data("id");
        $.getJSON(cfg.routes.payoutAccounts, function (res) {
            const a = (res.data || []).find((x) => x.id === id);
            if (!a) return;
            $("#payoutId").val(a.id);
            $type.val(a.type);
            togglePayoutFields();
            $("#holderName").val(a.holder_name || "");
            $("#accNumber").val("");
            $("#accConfirm").val("");
            $("#ifsc").val(a.ifsc || "");
            $("#bankName").val(a.bank_name || "");
            $("#branch").val(a.branch || "");
            $("#upiVpa").val(a.upi_vpa || "");
            $("#paypalEmail").val(a.paypal_email || "");
            $("#isDefault").prop("checked", !!a.is_default);
            $modalPayout.modal("show");
        });
    });

    $list.on("click", ".act-del", function () {
        const id = $(this).data("id");
        Swal.fire({
            title: "Delete payout account?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Delete",
        }).then((r) => {
            if (!r.isConfirmed) return;
            $.ajax({
                url: cfg.routes.payoutDelete.replace(":id", id),
                type: "DELETE",
                data: { _token: csrf },
            })
                .done(() => {
                    swalToast.fire({ icon: "success", title: "Deleted" });
                    loadPayoutAccounts();
                })
                .fail((xhr) =>
                    Swal.fire({
                        icon: "error",
                        title: "Delete failed",
                        text:
                            (xhr.responseJSON && xhr.responseJSON.message) ||
                            "Error",
                    })
                );
        });
    });

    $list.on("click", ".act-default", function () {
        const id = $(this).data("id");
        $.post(cfg.routes.payoutDefault.replace(":id", id), { _token: csrf })
            .done(() => {
                swalToast.fire({ icon: "success", title: "Set as default" });
                loadPayoutAccounts();
            })
            .fail(() =>
                Swal.fire({ icon: "error", title: "Failed to set default" })
            );
    });

    $("#btnSavePayout").on("click", function () {
        const data = $form
            .serializeArray()
            .reduce((o, i) => ((o[i.name] = i.value), o), {});
        data._token = csrf;
        $.post(cfg.routes.payoutSave, data)
            .done(() => {
                swalToast.fire({ icon: "success", title: "Saved" });
                $modalPayout.modal("hide");
                loadPayoutAccounts();
            })
            .fail((xhr) => {
                const msg =
                    (xhr.responseJSON &&
                        (xhr.responseJSON.message ||
                            JSON.stringify(xhr.responseJSON.errors))) ||
                    "Validation failed";
                Swal.fire({ icon: "error", title: "Save failed", text: msg });
            });
    });

    // Load on page ready (if section exists)
    loadPayoutAccounts();

    // ===== Add Funds =====
    const $modalAdd = $("#modalAddFunds");
    const $addAmount = $("#addAmount");
    const $btnAddOpen = $("#btnAddFunds");
    const $btnAddConfirm = $("#confirmAddFunds");

    $btnAddOpen.on("click", function () {
        $addAmount.val("");
        $modalAdd.modal("show");
    });

    // Simple chooser using SweetAlert
    function chooseGateway() {
        return Swal.fire({
            title: "Select payment gateway",
            input: "radio",
            inputOptions: { razorpay: "Razorpay", paypal: "PayPal" },
            inputValidator: (v) => !v && "Please select a gateway",
            showCancelButton: true,
            confirmButtonText: "Continue",
        });
    }

    $btnAddConfirm.on("click", async function () {
        const amount = parseFloat($addAmount.val());
        if (isNaN(amount) || amount < 1) {
            Swal.fire({
                icon: "warning",
                title: "Enter a valid amount",
                text: "Minimum 1.00 required.",
            });
            return;
        }

        const currency = userCurrency();
        const { value: gw, isConfirmed } = await chooseGateway();
        if (!isConfirmed) return;

        if (gw === "razorpay") {
            // Existing Razorpay flow
            $.ajax({
                url: cfg.routes.createOrder,
                type: "POST",
                data: { amount: amount, currency: currency, _token: csrf },
                success: function (payload) {
                    $modalAdd.modal("hide");

                    const options = {
                        key: payload.key,
                        amount: payload.order.amount,
                        currency: payload.order.currency,
                        name: "Wallet Top-up",
                        description: "Add funds to wallet",
                        order_id: payload.order.id,
                        prefill: { name: cfg.user.name, email: cfg.user.email },
                        handler: function (response) {
                            // verify on server
                            Swal.fire({
                                title: "Verifying payment...",
                                didOpen: () => Swal.showLoading(),
                                allowOutsideClick: false,
                            });
                            $.ajax({
                                url: cfg.routes.callback,
                                type: "POST",
                                data: {
                                    _token: csrf,
                                    razorpay_order_id:
                                        response.razorpay_order_id,
                                    razorpay_payment_id:
                                        response.razorpay_payment_id,
                                    razorpay_signature:
                                        response.razorpay_signature,
                                    amount: amount,
                                    currency_symbol: cfg.symbol,
                                },
                                success: function () {
                                    Swal.close();
                                    swalToast.fire({
                                        icon: "success",
                                        title: "Funds added!",
                                    });
                                    const current =
                                        parseFloat(
                                            $("#wallet-balance")
                                                .text()
                                                .replace(/,/g, "")
                                        ) || 0;
                                    const next = current + amount;
                                    $("#wallet-balance").text(fmt(next));
                                    table.ajax.reload(null, false);
                                },
                                error: function () {
                                    Swal.fire({
                                        icon: "error",
                                        title: "Payment verification failed",
                                    });
                                    table.ajax.reload(null, false);
                                },
                            });
                        },
                    };
                    const rzp = new Razorpay(options);
                    rzp.open();
                },
                error: function () {
                    Swal.fire({
                        icon: "error",
                        title: "Failed to create order",
                    });
                },
            });
        } else {
            // PayPal flow: server should return { orderID, approveUrl }
           try {
  const orderRes = await $.post(cfg.routes.paypalOrder, {
    _token: csrf,
    amount,
    currency,
  });

  const orderID = orderRes.orderID;
  const approveUrl =
    orderRes.approveUrl ||
    (orderID
      ? ((window.WALLET_PAGE.paypalMode || 'sandbox') === 'live'
          ? `https://www.paypal.com/checkoutnow?token=${orderID}`
          : `https://www.sandbox.paypal.com/checkoutnow?token=${orderID}`)
      : null);

  if (!orderID || !approveUrl) {
    throw new Error('Unable to create PayPal order');
  }

  // Open PayPal approval window
  const popup = window.open(approveUrl, 'paypal_approve', 'width=600,height=700');
  if (!popup) throw new Error('Popup blocked. Please allow pop-ups and retry.');

  let approved = false;

  // Listen for approval/cancel from the return/cancel pages
  const onMessage = (ev) => {
    if (!ev || !ev.data || typeof ev.data !== 'object') return;

    if (ev.data.type === 'PAYPAL_APPROVED') {
      if (!ev.data.orderID) return;
      approved = true;
      window.removeEventListener('message', onMessage);

      Swal.fire({
        title: 'Capturing...',
        didOpen: () => Swal.showLoading(),
        allowOutsideClick: false,
      });

      $.post(cfg.routes.paypalCapture, { _token: csrf, orderID: ev.data.orderID })
        .done(() => {
          Swal.close();
          $modalAdd.modal('hide');
          swalToast.fire({ icon: 'success', title: 'Funds added!' });
          const current = parseFloat($('#wallet-balance').text().replace(/,/g, '')) || 0;
          $('#wallet-balance').text(fmt(current + amount));
          table.ajax.reload(null, false);
        })
        .fail((xhr) => {
          Swal.fire({
            icon: 'error',
            title: 'Capture failed',
            text: (xhr.responseJSON && xhr.responseJSON.message) || 'Error',
          });
        });
    }

    if (ev.data.type === 'PAYPAL_CANCELLED') {
      window.removeEventListener('message', onMessage);
      Swal.fire({ icon: 'info', title: 'Payment cancelled' });
    }
  };

  window.addEventListener('message', onMessage);

  // Safety: user closes popup without approving
  const watchdog = setInterval(() => {
    if (popup.closed) {
      clearInterval(watchdog);
      setTimeout(() => {
        if (!approved) {
          window.removeEventListener('message', onMessage);
          Swal.fire({ icon: 'error', title: 'Payment not approved' });
        }
      }, 400);
    }
  }, 700);

} catch (e) {
  Swal.fire({
    icon: 'error',
    title: 'PayPal payment failed',
    text: (e && e.message) || 'Error',
  });
}

        }
    });

    // ===== Withdraw (6 presets or custom → pending request → payout) =====
    const $modalWith = $("#modalWithdraw");
    const $btnWithOpen = $("#btnWithdraw");
    const $btnWithConfirm = $("#confirmWithdraw");
    const $presetCustom = $("#presetCustom");
    const $customWrap = $("#withdrawCustomWrap");
    const $withAmount = $("#withAmount");

    let selectedWithdrawAmount = null;

    function setWithdrawAmount(val) {
        selectedWithdrawAmount = val;
        $btnWithConfirm.prop(
            "disabled",
            !(selectedWithdrawAmount && selectedWithdrawAmount >= 1)
        );
    }

    // KYC + 2FA gate before opening
    $btnWithOpen.on("click", function () {
        const guards =
            cfg && cfg.guards
                ? cfg.guards
                : { kycApproved: false, twofaEnabled: false };
        if (!guards.kycApproved) {
            Swal.fire({
                icon: "warning",
                title: "KYC required",
                text: "Please complete KYC and wait for approval to withdraw.",
            });
            return;
        }
        if (!guards.twofaEnabled) {
            Swal.fire({
                icon: "warning",
                title: "2FA required",
                text: "Enable Two-Factor Authentication to withdraw.",
            });
            return;
        }

        // reset modal state
        $(".withdraw-preset").removeClass("active");
        $customWrap.addClass("d-none");
        $withAmount.val("");
        setWithdrawAmount(null);
        $modalWith.modal("show");
    });

    $(document).on("click", ".withdraw-preset", function () {
        $(".withdraw-preset").removeClass("active");
        $(this).addClass("active");
        $customWrap.addClass("d-none");
        $withAmount.val("");
        const amt = parseFloat($(this).data("amount"));
        setWithdrawAmount(isNaN(amt) ? null : amt);
    });

    $presetCustom.on("click", function () {
        $(".withdraw-preset").removeClass("active");
        $customWrap.removeClass("d-none");
        $withAmount.val("").trigger("focus");
        setWithdrawAmount(null);
    });

    $withAmount.on("input", function () {
        const amt = parseFloat($(this).val());
        setWithdrawAmount(isNaN(amt) ? null : amt);
    });

    // Helper: choose payout account (default preselected)
    async function choosePayoutAccount() {
        try {
            const res = await $.getJSON(cfg.routes.payoutAccounts);
            const accs = res.data || [];
            if (!accs.length) {
                await Swal.fire({
                    icon: "info",
                    title: "No payout account",
                    text: "Please add a payout account first.",
                });
                return null;
            }
            const opts = {};
            accs.forEach((a) => {
                opts[a.id] = `${a.type.toUpperCase()} • ${a.holder_name}${
                    a.type === "bank" ? " • " + (a.masked_account || "") : ""
                }${a.is_default ? " • DEFAULT" : ""}`;
            });
            const def = (accs.find((a) => a.is_default) || accs[0]).id;
            const { value: picked, isConfirmed } = await Swal.fire({
                title: "Select payout account",
                input: "select",
                inputOptions: opts,
                inputValue: def,
                showCancelButton: true,
                confirmButtonText: "Continue",
            });
            return isConfirmed ? picked : null;
        } catch {
            return null;
        }
    }

    $btnWithConfirm.on("click", async function () {
        const amount = selectedWithdrawAmount;
        if (!amount || amount < 1) {
            Swal.fire({
                icon: "warning",
                title: "Enter a valid amount",
                text: "Minimum 1.00 required.",
            });
            return;
        }

        const accId = await choosePayoutAccount();
        if (!accId) return;

        Swal.fire({
            title: "Confirm withdrawal",
            text: `Withdraw ${cfg.symbol} ${fmt(amount)}?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Yes, withdraw",
            cancelButtonText: "Cancel",
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: cfg.routes.withdraw,
                type: "POST",
                data: {
                    amount: amount,
                    currency_symbol: cfg.symbol,
                    payout_account_id: accId,
                    _token: csrf,
                },
                beforeSend: function () {
                    Swal.fire({
                        title: "Processing...",
                        didOpen: () => Swal.showLoading(),
                        allowOutsideClick: false,
                    });
                },
                success: function (res) {
                    Swal.close();
                    swalToast.fire({
                        icon: "success",
                        title: res.message || "Withdrawal successful",
                    });
                    $modalWith.modal("hide");
                    const current =
                        parseFloat(
                            $("#wallet-balance").text().replace(/,/g, "")
                        ) || 0;
                    const next = current - amount;
                    $("#wallet-balance").text(fmt(next));
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    Swal.close();
                    const msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : "Withdraw failed";
                    Swal.fire({
                        icon: "error",
                        title: "Withdraw failed",
                        text: msg,
                    });
                },
            });
        });
    });
})();
