<footer class="sticky-footer bg-white mt-5 full-width-footer" style="padding:1rem 0;background-color:#1F1F1F;background-image:linear-gradient(180deg, #033224 80%, #115641 300%)!important;color:white;">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <span>&copy; DAXTRO {{ date('Y') }}</span>
        </div>
    </div>
</footer>

<style>
.full-width-footer {
    margin-left: -224px !important;
    width: 100vw !important;
    position: relative !important;
}

.full-width-footer .container-fluid,
.full-width-footer .container {
    text-align: center !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    width: 100% !important;
    padding-left: 112px !important; /* Half of sidebar width (224px / 2) to center relative to full viewport */
}

.full-width-footer .copyright {
    text-align: center !important;
    width: 100% !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    margin: 0 auto !important;
}

.full-width-footer .copyright span {
    text-align: center !important;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .full-width-footer {
        margin-left: 0 !important;
        width: 100% !important;
    }
    .full-width-footer .container-fluid,
    .full-width-footer .container {
        padding-left: 0 !important; /* No offset needed on mobile */
    }
}

/* Sidebar toggled state */
.sidebar.toggled ~ #content-wrapper .full-width-footer {
    margin-left: -90px !important;
}

.sidebar.toggled ~ #content-wrapper .full-width-footer .container-fluid,
.sidebar.toggled ~ #content-wrapper .full-width-footer .container {
    padding-left: 45px !important; /* Half of collapsed sidebar width (90px / 2) */
}

/* Mobile sidebar toggled */
@media (max-width: 768px) {
    .sidebar.toggled ~ #content-wrapper .full-width-footer {
        margin-left: 0 !important;
    }
    .sidebar.toggled ~ #content-wrapper .full-width-footer .container-fluid,
    .sidebar.toggled ~ #content-wrapper .full-width-footer .container {
        padding-left: 0 !important;
    }
}
</style>
