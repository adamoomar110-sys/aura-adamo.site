# Hardware, IoT y Telemetría Industrial
## Diseño de Dispositivos Embebidos con ESP32, Protocolos Industriales Modbus y Conectividad Cloud Segura

**Dirección Editorial:** Omar Horacio Adamo  
**Sello Editorial:** Aura Ediciones (División Negocios y Tecnologías Exponenciales)  
**Colección:** Colección Aura: Emprendimiento y Negocios Digitales · Volumen 4 de 10  
**Copyright:** © 2026 Aura. Todos los derechos reservados. Plataforma fundada por Omar Horacio Adamo.

---

> ### ⚠️ Descargo de Responsabilidad de Ingeniería
> Los diagramas circuitales, parámetros de potencia y protocolos de firmware contenidos en esta publicación representan directrices profesionales para ingeniería electrónica y de sistemas embebidos. El manejo de voltajes industriales y conexiones de campo requiere personal calificado y cumplimiento estricto de las normativas de seguridad eléctrica vigentes. Aura Ediciones y su fundador Omar Horacio Adamo ofrecen este material como guía formativa y de referencia técnica.

---

## Índice General

- [Introducción: La Fusión del Mundo Físico con la Nube Digital](#introducción)
- [Capítulo 1: Selección del Microcontrolador: ESP32-S3 vs STM32 Industrial](#capítulo-1)
- [Capítulo 2: Protocolos de Comunicación Industrial: RS-485 Modbus RTU](#capítulo-2)
- [Capítulo 3: Diseño de Entradas/Salidas: Aislamiento Optoacoplado y Filtros ADC](#capítulo-3)
- [Capítulo 4: Gestión de Energía y Modos de Sueño Profundo (Deep Sleep)](#capítulo-4)
- [Capítulo 5: Firmware Robusto con FreeRTOS y Manejo de Tareas Asíncronas](#capítulo-5)
- [Capítulo 6: Conectividad Segura: MQTT sobre TLS (Puerto 8883) y Certificados X.509](#capítulo-6)
- [Capítulo 7: Almacenamiento Local Resiliente con SPIFFS y LittleFS](#capítulo-7)
- [Capítulo 8: Servidores de Telemetría: EMQX, InfluxDB y Tableros en Grafana](#capítulo-8)
- [Capítulo 9: Análisis Detallado de la Infografía Técnica Oficial Aura](#capítulo-9)
- [Capítulo 10: De la Prototipación en Laboratorio a la Fabricación de PCBs Industriales](#capítulo-10)

---

## Introducción
### La Fusión del Mundo Físico con la Nube Digital

El software puro tiene límites evidentes: no puede medir la temperatura de una caldera, registrar la presión de un circuito hidráulico ni controlar en tiempo real el acceso a un recinto industrial. La verdadera transformación digital y la creación de barreras de entrada inexpugnables se producen cuando el software se conecta con **hardware propio diseñado para el entorno físico**.

En la plataforma Aura, impulsada por Omar Horacio Adamo, este principio se materializa en el desarrollo de terminales telemáticas aplicadas al monitoreo de flotas vehiculares, el control automatizado de centros de lavado y la supervisión de maquinaria industrial. Estas soluciones integran sensores de campo con microcontroladores de última generación y los enlazan a la nube mediante protocolos ligeros, encriptados y resilientes a fallos de conectividad.

Este manual ofrece una guía integral para ingenieros, desarrolladores y emprendedores tecnológicos que desean diseñar hardware IoT robusto, confiable y listo para operar 24/7 en condiciones industriales adversas.

---

## Capítulo 1: Selección del Microcontrolador: ESP32-S3 vs STM32 Industrial

La elección del cerebro electrónico determina el costo, el consumo energético y la complejidad del desarrollo de firmware.

### 1.1 El ESP32-S3 de Espressif
- **Arquitectura:** Dual-Core Xtensa LX7 a 240 MHz con aceleración vectorial para IA básica.
- **Conectividad Integrada:** Wi-Fi 802.11 b/g/n y Bluetooth 5 (LE).
- **Seguridad en Silicio:** Cripto-coprocesador con soporte por hardware para AES-256, RSA y arranque seguro (*Secure Boot*).
- **Relación Costo-Beneficio:** Inigualable para terminales con conectividad inalámbrica nativa.

### 1.2 La Familia STM32 (Cortex-M4/M7)
Ideal para aplicaciones donde se requiere certificación de seguridad funcional SIL, buses CAN múltiples y temporizadores de ultra-alta precisión sin dependencias de radios inalámbricas.

---

## Capítulo 2: Protocolos de Comunicación Industrial: RS-485 Modbus RTU

El entorno industrial está plagado de ruido electromagnético generado por motores, variadores de frecuencia y contactores. Las líneas lógicas de 3.3V o 5V (UART/I2C) se corrompen en distancias mayores a un par de metros.

### 2.1 El Estándar Diferencial RS-485
RS-485 transmite señales mediante un par trenzado diferencial (líneas A y B). El receptor mide la diferencia de potencial:
$$V_{diff} = V_A - V_B$$
Cualquier ruido electromagnético afecta a ambas líneas simultáneamente, anulándose en el receptor gracias a la alta tasa de rechazo en modo común (CMRR). Esto permite transmisiones confiables de hasta 1200 metros a velocidades de 9600 a 115200 baudios.

### 2.2 Trama Modbus RTU
Una trama Modbus típica comprende:
`[Dirección del Esclavo (1B)] [Código de Función (1B)] [Dirección de Registro (2B)] [Datos (NB)] [CRC-16 (2B)]`
El microcontrolador actúa como maestro, interrogando cíclicamente a los sensores de campo con verificación matemática de integridad mediante CRC-16.

---

## Capítulo 3: Diseño de Entradas/Salidas: Aislamiento Optoacoplado y Filtros ADC

Conectar directamente un pin de entrada analógica de un microcontrolador a un sensor industrial es una invitación al desastre.

### 3.1 Aislamiento Galvánico con Optoacopladores
Para entradas digitales (pulsos, finales de carrera, sensores inductivos), se utilizan optoacopladores rápidos (como el PC817 o 6N137). La señal eléctrica se convierte en fotones emitidos por un LED interno y captados por un fototransistor, eliminando por completo cualquier enlace galvánico entre el campo (24V DC) y la placa lógica (3.3V DC).

### 3.2 Lazos de Corriente 4-20 mA
Los sensores analógicos de alta precisión emplean corriente en lugar de voltaje. El lazo 4-20 mA es inmune a la caída de tensión en el cableado:
- 4 mA representa el cero de la escala (permite detectar rotura de cable si la corriente cae a 0 mA).
- 20 mA representa el fondo de escala.
- En la placa, una resistencia de precisión de 125 $\Omega$ (0.1%) convierte los 4-20 mA en una señal de 0.5V a 2.5V, ideal para el conversor analógico-digital (ADC) del microcontrolador.

---

## Capítulo 4: Gestión de Energía y Modos de Sueño Profundo (Deep Sleep)

En dispositivos alimentados por baterías o paneles solares, el consumo energético debe ser minimizado con precisión microamperimétrica.

### 4.1 Consumos Típicos en ESP32
- **Modo Activo con WiFi Tx:** 180 a 240 mA.
- **Modo Módem Sleep:** 20 a 30 mA.
- **Modo Deep Sleep:** 10 a 15 $\mu$A (apaga núcleos principales, radios y memorias, manteniendo activo únicamente el coprocesador ULP y el reloj RTC).

### 4.2 Ciclo de Trabajo Eficiente (Duty Cycling)
1. Despertar por temporizador RTC cada 15 minutos.
2. Encender la alimentación de los sensores externos mediante un transistor MOSFET canal P.
3. Leer los sensores vía RS-485 en 400 milisegundos.
4. Conectar a WiFi, transmitir el paquete MQTT con TLS y esperar acuse de recibo (ACK).
5. Apagar periféricos y regresar a Deep Sleep en menos de 2.5 segundos.
- *Resultado:* Años de autonomía con una batería de litio LiFePO4 de 3200 mAh.

---

## Capítulo 5: Firmware Robusto con FreeRTOS y Manejo de Tareas Asíncronas

Un dispositivo industrial nunca debe colgarse ni congelarse ante una excepción de red o un sensor desconectado.

### 5.1 Arquitectura Multi-Hilo en FreeRTOS
Se asignan tareas con prioridades diferenciadas en los dos núcleos del ESP32:
- **Núcleo 0 (Protocol Core):** Tarea de gestión de pila TCP/IP, reconexión WiFi y cliente MQTT.
- **Núcleo 1 (Application Core):** Tarea de muestreo de sensores a intervalos estrictos y tarea de supervisión mediante temporizador guardián (*Watchdog Timer*).
Si alguna tarea entra en bucle infinito por más de 5 segundos, el Watchdog reinicia el microcontrolador de forma limpia, registrando el código de fallo en la memoria flash no volátil.

---

## Capítulo 6: Conectividad Segura: MQTT sobre TLS (Puerto 8883) y Certificados X.509

Transmitir datos de telemetría o comandos de control en texto plano (puerto 1883 sin cifrar) es una vulnerabilidad inaceptable que expone a la empresa al espionaje y sabotaje industrial.

### 6.1 Autenticación Mutua mTLS
En la arquitectura Aura IoT:
- El servidor broker valida la identidad del dispositivo mediante un certificado X.509 único grabado en la memoria protegida del microcontrolador.
- El microcontrolador verifica la huella criptográfica del certificado de la Autoridad Certificadora (CA) del broker.
- Toda la sesión se cifra mediante TLS 1.3 con intercambio de claves Diffie-Hellman y algoritmo simétrico AES-GCM-256.

---

## Capítulo 7: Almacenamiento Local Resiliente con SPIFFS y LittleFS

Las conexiones a internet en instalaciones industriales y vehículos son intrínsecamente inestables. Un dispositivo no puede perder mediciones críticas mientras atraviesa una zona sin cobertura celular.

### 7.1 El Buffer Circular en Memoria Flash
El firmware implementa un buffer persistente en memoria LittleFS con capacidad para almacenar más de 10,000 registros con marca temporal (timestamp). Cuando la conexión MQTT se interrumpe:
1. El dispositivo conmuta automáticamente a modo local (*Offline Mode*).
2. Los datos muestreados se escriben en bloques de flash organizados como cola FIFO (First-In, First-Out).
3. Al restablecerse el enlace TLS, una tarea secundaria drena la cola en ráfagas ordenadas sin saturar la red.

---

## Capítulo 8: Servidores de Telemetría: EMQX, InfluxDB y Tableros en Grafana

En el lado del servidor, la infraestructura debe procesar millones de mensajes entrantes con latencia mínima.

### 8.1 La Pila Tecnológica de Telemetría
- **Broker MQTT:** EMQX distribuido en clusters para soportar miles de conexiones simultáneas concurrentes con baja huella de memoria.
- **Base de Datos de Series Temporales (TSDB):** InfluxDB o TimescaleDB optimizadas para consultas analíticas sobre marcas temporales continuas y agregaciones matemáticas (medias, picos, desviaciones).
- **Visualización y Alertas:** Dashboards en Grafana con paneles configurables y alertas automáticas disparadas por umbrales anómalos directamente hacia Telegram y WhatsApp Business.

---

## Capítulo 9: Análisis Detallado de la Infografía Técnica Oficial Aura

En esta sección se analiza en profundidad la topología del sistema de telemetría y control embebido:

![Infografía Técnica Oficial Aura - Topología de Telemetría Industrial IoT](../06_Diagramas_e_Ilustraciones/diag_04_iot_telemetria.png)

### Explicación Paso a Paso del Diagrama
1. **Instrumentación de Campo (Bloque 1):** Muestra la conexión de sensores industriales de temperatura, presión y caudal operando sobre lazos 4-20mA y buses RS-485 Modbus RTU con protección galvánica.
2. **Microcontrolador y Firmware (Bloque 2):** Detalla el chip ESP32-S3 ejecutando FreeRTOS con asignación dual-core, aislamiento por optoacopladores y firmware en C++ de alta eficiencia.
3. **Broker Cloud Seguro (Bloque 3):** Refleja la transmisión telemática sobre el puerto seguro 8883 utilizando MQTT encapsulado en TLS, con calidad de servicio QoS 1 y control de latidos (*KeepAlive*).
4. **Almacenamiento y Visualización (Bloque 4):** Ilustra la persistencia en InfluxDB y el renderizado analítico en Grafana con disparo de notificaciones inmediatas ante fallos de operación.
5. **Panel Inferior de Parámetros Críticos:** Fija el estándar de Aura: consumo residual en Deep Sleep de 12 $\mu$A, buffer local de 10,000 registros en flash SPIFFS y autenticación mutua mTLS basada en certificados X.509.

---

## Capítulo 10: De la Prototipación en Laboratorio a la Fabricación de PCBs Industriales

| Fase de Producción | Herramientas & Métodos | Criterios de Aprobación |
| :--- | :--- | :--- |
| **Prototipo Breadboard** | Módulos DevKit, osciloscopio, analizador lógico USB. | Validación funcional de lógica y librerías en 48 horas. |
| **Diseño Esquemático** | KiCad / EasyEDA; planos de tierra masivos; filtrado LC. | Revisión de reglas de diseño (DRC) e inmunidad EMC. |
| **Ruteo de PCB (Layout)** | 2 o 4 capas; trazas de señal de 50 $\Omega$; pistas de potencia anchas. | Separación galvánica de 3 mm entre alta y baja tensión. |
| **Ensamble SMD & Pruebas** | Fabricación en JLCPCB/PCBWay con pick-and-place automatizado. | Test funcional del 100% de placas en banco de prueba. |

---

*© 2026 Aura. Todos los derechos reservados. Plataforma fundada por Omar Horacio Adamo.*
