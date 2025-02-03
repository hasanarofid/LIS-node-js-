const WebSocket = require("ws");
var time = null;
var data_b = [];
data_b[1] = [];

// Membuat server WebSocket pada port 1177 
const wss = new WebSocket.Server({ port: 1177 });

wss.on("connection", function connection(ws) {
  console.log("Klien terhubung.");

  ws.on("message", function incoming(message) {
    // console.log(JSON.parse(message));

    savemessage(message);
  });

  ws.on("close", function close() {
    console.log("Klien terputus.");
  });
});
const { exec } = require("child_process");
const schedule = require("node-schedule");

function savemessage(message) {
  try {
    const data = JSON.parse(message);
    const { temp, hum, ip_address, mac_address, timestamp } = data;

    // console.log(
    //   "Data yang diterima: | temp : ",
    //   parseInt(temp),
    //   "|hum : ",
    //   parseInt(hum),
    //   "| IP Address : ",
    //   ip_address,
    //   "| MAC Address : ",
    //   mac_address,
    //   "| Timestamp : ",
    //   timestamp
    // );

    // console.log("time : ", time);

    if (!data_b[mac_address]) {
      data_b[mac_address] = { temp: -999, hum: -999 };
    }
    var temp_lama = parseInt(data_b[mac_address].temp); //data_b[mac_address].temp; 
    var validasi_temp = Math.abs(temp_lama - parseInt(temp)) >= 2;
    
    var hum_lama = parseInt(data_b[mac_address].hum); //data_b[mac_address].temp; 
    var validasi_hum = Math.abs(hum_lama - parseInt(hum)) >= 2;
    
    // console.log('validasi : ',validasi_temp);
    // console.log('validasi : ',validasi_temp);
    

    if (
      time != timestamp.substring(11, 16) ||
      validasi_temp ||
      validasi_hum
      // parseInt(data_b[mac_address].temp) != parseInt(temp) ||
      // parseInt(data_b[mac_address].hum) != parseInt(hum)
    ) {
      time = timestamp.substring(11, 16);
      data_b[mac_address] = { temp: temp, hum: hum };
      // console.log("data setelah :", data_b);

      // Membuat objek data yang akan dikirim
      const postData = {
        temp: temp,
        hum: hum,
        ip_address: ip_address,
        mac_address: mac_address,
        timestamp: timestamp,
      };

      // Mengirim data ke endpoint menggunakan node-fetch
      const fetch = require("node-fetch");
      fetch("https://e-mon.rsudrsoetomo.jatimprov.go.id/api/getdata", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(postData),
      })
        .then((response) => response.json())
        .then((data) => {
          console.log("Sukses:", data);
        })
        .catch((error) => {
          console.error("Error:", error);
        });
    }
  } catch (error) {
    console.error("Error saat parsing data:", error);
  }
}

// Menjadwalkan PM2 flush setiap jam 12 malam
schedule.scheduleJob("0 0 * * *", function () {
  exec("pm2 flush", (error, stdout, stderr) => {
    if (error) {
      console.error(`Error saat menjalankan pm2 flush: ${error}`);
      return;
    }
    console.log("PM2 log berhasil di-flush pada pukul 12 malam.");
  });
});
