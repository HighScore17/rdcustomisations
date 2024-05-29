<style>
  #wpwrap {
    background-color: #18191a;
    color: #fff;
  }

  .side-menu {
    width: 250px;
    background-color: 	#242526;
    padding: 10px;
    border-radius: 5px;
  }

  .side-menu ul li:not(:last-child) {
    margin-bottom: 20px;
  }

  .side-menu ul li a {
    display: block;
    padding: 5px 10px;
    transition: 300ms;
  }
  .side-menu ul li a:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: #fff;
    border-radius: 3px;
  }

  .main-container {
    display: flex;
    padding: 20px 20px 20px 0px;
    column-gap: 30px;
  }
  .main-container * {
    box-sizing: border-box;
  }
  .integration-content {
    flex-grow: 1;
    width: 100%;
    background-color: 	#242526;
    border-radius: 5px;
    padding: 10px 20px;


  }
  .integration-content input{
    background-color:  rgba(0, 0, 0, 0.3) !important;
    color: #fff !important;
  }

  @media screen and (max-width: 768px) {
    .main-container {
      flex-direction: column;
      
    }
    .side-menu {
      width: 100%;
      margin-bottom: 20px;
    }
  }
</style>