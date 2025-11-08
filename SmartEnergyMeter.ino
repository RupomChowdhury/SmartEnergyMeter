#include <WiFi.h>
#include <WiFiClient.h>
#include <HTTPClient.h>
#include "time.h"
#include <Wire.h>
#include "ACS712.h"
#include <ZMPT101B.h>
#include <EEPROM.h>

char ssid[] = "SixT9";
char pass[] = "wifi.rupom.dev";

ACS712   ACS(34, 3.3, 4096, 66);
ZMPT101B voltageSensor(35, 50.0);

float unit;                 
int   volt, current, power; 

#define EEPROM_SIZE   512
#define UNIT_ADDRESS  0

const char* API_URL   = "http://192.168.1.100/meter/ingest.php"; 
const char* DEVICE_ID = "esp32meter";               
const char* API_KEY   = "LUb0FTb+UjvgGSuyXZDBU+lBy2Y9Ixdc8a+KZqZ9taA=";      

const unsigned long SEND_INTERVAL_MS = 1000;
unsigned long lastSend = 0;

void connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, pass);
  Serial.print("Connecting to WiFi");

  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED) {
    Serial.print('.');
    delay(500);
    if (millis() - start > 20000) break; 
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("WiFi OK. IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("WiFi failed (will retry later).");
  }
}

void maybeSendToServer(float voltage, float mA, float watt, float kWhTotal) {
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
    if (WiFi.status() != WL_CONNECTED) return;
  }

  if (millis() - lastSend < SEND_INTERVAL_MS) return;
  lastSend = millis();

  HTTPClient http;
  http.begin(API_URL);
  http.addHeader("Content-Type", "application/json");

  String payload = "{";
  payload += "\"device_id\":\"" + String(DEVICE_ID) + "\",";
  payload += "\"api_key\":\""   + String(API_KEY)   + "\",";
  payload += "\"voltage\":"     + String(voltage, 2) + ",";
  payload += "\"current_ma\":"  + String(mA, 2)      + ",";
  payload += "\"power_w\":"     + String(watt, 2)    + ",";
  payload += "\"energy_kwh_current\":" + String(kWhTotal, 6);
  payload += "}";

  int code = http.POST(payload);
  if (code > 0) {
    String resp = http.getString();
    Serial.print("POST "); Serial.print(code); Serial.print(" => ");
    Serial.println(resp);
  } else {
    Serial.print("POST failed: ");
    Serial.println(http.errorToString(code));
  }
  http.end();
}

void setup() {
  Serial.begin(115200);

  EEPROM.begin(EEPROM_SIZE);
  unit = EEPROM.readFloat(UNIT_ADDRESS);
  if (isnan(unit)) unit = 0.0;

  Serial.print("Previous Unit Value: ");
  Serial.println(unit);

  Serial.print("ACS712_LIB_VERSION: ");
  Serial.println(ACS712_LIB_VERSION);

  ACS.autoMidPoint();
  Serial.print("MidPoint: ");
  Serial.println(ACS.getMidPoint());
  Serial.print("Noise mV: ");
  Serial.println(ACS.getNoisemV());

  voltageSensor.setSensitivity(500.0f);

  delay(1000);
  connectWiFi();
}

void loop() {
  int noise = ACS.getNoisemV();
  float average = 0;
  for (int i = 0; i < 100; i++) {
    average += ACS.mA_AC();
  }
  float mA = (average / 100.0) - noise;

  (mA > 5) ? current = mA : current = 0;

  float voltage = voltageSensor.getRmsVoltage();
  (voltage > 50) ? volt = voltage : volt = 0;

  float watt = voltage * (mA / 1000.0);
  power = watt;
  float kWh = watt / 3600; 
  unit = kWh;

  Serial.print("Voltage: ");
  Serial.print(volt);
  Serial.println(" V");

  Serial.print("Current: ");
  Serial.print(current);
  Serial.println(" mA");

  Serial.print("Power: ");
  Serial.print(power);
  Serial.println(" W");

  Serial.print("Energy: ");
  Serial.print(unit, 4);
  Serial.println(" kWh");

  delay(500);

  EEPROM.writeFloat(UNIT_ADDRESS, unit);
  EEPROM.commit();

  float safeV = (voltage < 50) ? 0 : voltage;
  float safeI = (mA <= 15) ? 0 : mA;
  float safeP = (watt < 0.25) ? 0 : watt;
  float safeE = (unit < 0.0040) ? 0 : unit;

  maybeSendToServer(safeV, safeI, safeP, safeE);
}
