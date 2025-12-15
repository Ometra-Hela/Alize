# Guía técnica para agente de IA  
## Implementación del proceso de Portabilidad Numérica en México (NUMLEX/ABD) – Versión 2.1

> Documentos base:  
> - **Proceso de Portación de Número(s) – Especificación para México v2.1 (2019-12-19)**  
> - **Especificación de las Interfaces de Portabilidad Numérica en México v2.1 (2019-12-19)**  
> - **Códigos de Error de Portabilidad Numérica México v2.1 (2019-12-19)**  
> - **Guía del Usuario de la Interfaz NUMLEX ABD v2.1 (2019-12-27)**

---

## 1. Objetivo del agente

Guiar la implementación completa (backend + integración) de un módulo que:

1. Genere, envíe, reciba y valide mensajes de Portabilidad Numérica.  
2. Orqueste el flujo de estados de portación conforme a temporizadores y reglas ABD.  
3. Gestione adjuntos cuando el proceso lo requiere.  
4. Sincronice resultados por mensajería y por archivos diarios.  

El agente debe producir código, pruebas, y documentación interna del sistema.

---

## 2. Alcance funcional recomendado

### 2.1 Procesos principales
- Portación (mensajes 1001–1093).  
- Generación y confirmación de NIP (2001–2005) para Persona Física cuando aplique.  
- Cancelación de portación (3001–3002).  
- Reversión de portación (4001–4005).  
- Eliminación de número portado (5001–5002).  
- Alta de número no geográfico (6001–6002) si el negocio lo requiere.  
- Sincronización (7001–7002).  
- Asociaciones IDA/CR (8101–8202) si se integra catálogo nacional.

> Si tu producto solo maneja **portaciones móviles**, puedes deshabilitar flujos fijos/no geográficos a nivel de UI y validaciones internas, manteniendo compatibilidad estructural con el estándar.

---

## 3. Actores y conceptos clave

- **ABD/NUMLEX**: Base de Datos Administrativa que orquesta el proceso.  
- **RIDA**: Proveedor de Servicios Receptor.  
- **DIDA**: Proveedor de Servicios Donante.  
- **RCR/DCR**: Concesionarios de Red Receptor/Donante.  

Regla de consistencia: **DIDA y RIDA pueden ser el mismo participante, pero DCR y RCR nunca pueden tener el mismo valor.**

---

## 4. Arquitectura de integración

### 4.1 Interfaz SOAP (mensajería en línea)

**Servicio:** NPCWebService  
**Método:** `processNPCMsg(userID, password, xmlMsg)`  
- `password` debe enviarse **codificada en Base64**.  
- `xmlMsg` es un XML que debe cumplir el esquema XSD.  

**Endpoint ABD (producción según doc v2.1):**  
`https://soap.portabilidad.mx/api/np/processmsg`

**Reglas operativas clave**
- Una solicitud SOAP debe contener **un solo mensaje de aplicación NPC**.  
- El usuario SOAP debe pertenecer al participante indicado como **Sender/Emisor** del mensaje.  
- Se requieren certificados TLS válidos para HTTPS.

### 4.2 Adjuntos en SOAP

Los mensajes que aceptan adjuntos usan la sección de adjuntos del SOAP:

- Tipos esperados: **gif, pdf, jpg** (si se recibe otro, se asume pdf).  
- El mensaje debe incluir:
  - **Número total de adjuntos.**
  - **Nombres de archivos.**
  - **Orden de adjuntos** igual al orden de nombres.
- Tamaño máximo total de adjuntos por mensaje: **4 MB**.

> Para Persona Física, portaciones Móvil/Fijo y **RecoveryFlag = No**, los adjuntos **no están permitidos**.

### 4.3 Archivos diarios (batch)

El ABD genera archivos diarios relevantes para:
- **Números Portados.**
- **Números Eliminados.**

El módulo debe soportar ingestión programada (SFTP o mecanismo definido por tu operación) y reconciliar estos archivos con el estado interno de cada proceso.

---

## 5. Estructura XML base

El esquema principal define un contenedor del mensaje:

```xml
<NPCData>
  <MessageHeader>
    <!-- Header obligatorio -->
  </MessageHeader>
  <NPCMessage>
    <!-- Un solo mensaje de aplicación -->
  </NPCMessage>
</NPCData>
```

### 5.1 MessageHeader (mínimo recomendado)

Campos obligatorios típicos:
- `TransTimestamp`
- `Sender`
- `NumOfMessages` (si se usa, para esta interfaz debe ser 1)

---

## 6. Identificadores y formatos críticos

### 6.1 PortID
Longitud 21, formato:
`IDA + YYYYMMDDhhmmss + nnnn`

Aplicable a varios mensajes, incluyendo 1001, 2001, 5001, 6001, 7001, 8101 y 8201.

### 6.2 FolioID (en Port Request)
Longitud 18, formato:
`IDA + AAMMDDhhmm + nnnnn`

Reglas:
- Debe ser único dentro del flujo de mensajes de portación asociado.

### 6.3 Timestamps
Formato estricto:
`AAAAMMDDhhmmss`

---

## 7. Máquina de estados sugerida (vista del sistema)

Usa los estados definidos por ABD como fuente de verdad y refleja un espejo interno.

Estados típicos observables en la especificación:
- `INITIAL`
- `PIN_REQUESTED`
- `PIN_DELIVERY_CONF`
- `PORT_REQUESTED`
- `PORT_INDVAL_REQUESTED`
- `READY_TO_BE_SCHEDULED`
- `REJECT_PENDING`
- `PORT_SCHEDULED`
- `PORTED`
- `CANCELLED`
- Estados de reversión (`REVERSAL_*`) si habilitados.

### 7.1 Transiciones clave (resumen)
1. **1001** (RIDA→ABD) inicia proceso.  
2. ABD responde con **1002** al RIDA y envía **1003** al DIDA.  
3. DIDA responde con **1004** dentro del temporizador T1.  
4. Si procede, ABD envía **1005** (lista para programar).  
5. RIDA envía **1006** dentro de T3.  
6. ABD notifica a todos con **1007** y actualiza el estado a `PORT_SCHEDULED`.  
7. La portación se marca como `PORTED` cuando se genera el archivo diario de números portados.

### 7.2 Cancelación
- El RIDA puede enviar **3001** si el estado es:
  - `PORT_REQUESTED`
  - `PORT_INDVAL_REQUESTED`
  - `READY_TO_BE_SCHEDULED`
  - `PORT_SCHEDULED`
- ABD valida que **no haya expirado T4** y responde con **3002**.

### 7.3 Reglas horarias de programación (1006)
- Si el mensaje 1006 llega **antes de las 21:59**, `PortExecDate` puede ser el siguiente día hábil.  
- Si llega **después de las 21:59**, `PortExecDate` debe ser **al menos un día hábil adicional**.

---

## 8. Tabla de mapeo de mensajes (núcleo)

| Proceso | ID | Nombre oficial | Tipo XSD / MsgType | Dirección | Dispara estado/acción interna |
|---|---:|---|---|---|---|
| Portación | 1001 | Solicitud de Portación | `PortReqMsgType` | RIDA→ABD | Crear proceso, validar NIP/folio/adjuntos/rangos |
| Portación | 1002 | Acuse de Solicitud | `PortRequestAckMsgType` | ABD→RIDA | Confirmar recepción/validaciones ABD |
| Portación | 1003 | Solicitud de Portación | `PortReqMsgType` | ABD→DIDA | Abrir ventana de respuesta Donante |
| Portación | 1004 | Respuesta de Portación | `PortRespMsgType` | DIDA→ABD | Aceptar/Rechazar/Rechazo parcial |
| Portación | 1005 | Portación lista para programar | *(ver XSD)* | ABD→RIDA/DIDA | Cambiar a READY_TO_BE_SCHEDULED |
| Portación | 1006 | Portación Programada | `SchedulePortMsgType` | RIDA→ABD | Proponer fecha de ejecución |
| Portación | 1007 | Portación Programada | `SchedulePortMsgType` | ABD→Todos | Cambiar a PORT_SCHEDULED |
| Portación | 1091 | Portación Rechazada | `RejectMsgType` | ABD→RIDA/DIDA | Finalizar como rechazo |
| Portación | 1092 | Portación Terminada | `RejectMsgType` | ABD→RIDA/DIDA | Finalizar por expiraciones/reglas |
| Portación | 1093 | Rechazo parcial | *(ver XSD)* | ABD→RIDA/DIDA | Ajustar resultado por número |
| Validación PF | 1201 | Solicitud validación PF | `IndividualPortValidationMsgType` | ABD→DIDA | Validación adicional PF |
| Validación PF | 1202 | Respuesta validación PF | `IndPortValRespMsgType` | DIDA→ABD | Aceptar/Rechazar validación |
| Validación PF | 1203 | Solicitud validación PF | *(ver XSD)* | ABD→RIDA | Sustituye 1002 en caso especial |
| Cancelación | 3001 | Solicitud de Cancelación | `PortCancelReqMsgType` | RIDA→ABD | Solicitar cancelación, validar T4 |
| Cancelación | 3002 | Aceptación de Cancelación | `PortCancelRespMsgType` | ABD→Actores | Cambiar a CANCELLED |
| Reversión | 4001 | Solicitud de Reversión | `PortRevReqMsgType` | DIDA→ABD | Inicio reversión (validar T5) |
| Reversión | 4002 | Solicitud docs reversión | `RevDocReqMsgType` | ABD→RIDA | Solo mutuo acuerdo |
| Reversión | 4003 | Respuesta docs reversión | `RevDocRespMsgType` | RIDA→ABD | Aceptar/Rechazar docs |
| Reversión | 4004 | Aceptación de Reversión | `PortRevAcceptMsgType` | ABD→Actores | Programar reversión |
| Reversión | 4005 | Rechazo de Reversión | `RejectMsgType` | ABD→Actores | Rechazar reversión |
| NIP | 2001 | Solicitud Generación NIP | `PinGenerationRequestMsgType` | RIDA/DIDA→ABD | Generar NIP PF |
| NIP | 2002 | Confirmación Entrega NIP | `PinDeliveryConfirmMsgType` | ABD→RIDA/DIDA | Habilita 1001 PF |
| NIP | 2004 | Confirmación de NIP | `PinConfirmMsgType` | Gateway→ABD | Recepción de SMS |
| NIP | 2005 | Notificación de NIP | `PinConfirmMsgType` | ABD→Originador | Eco de confirmación |

> Para tablas completas de mensajes y demás procesos (500x, 600x, 700x, 810x, 820x) usar el catálogo oficial del documento “Procesos de Portación”.

---

## 9. Plantillas XML (skeletons)

> **Nota:** Los namespaces y ubicaciones de esquema deben tomarse del WSDL/XSD oficial de tu entorno.  
> Estas plantillas son estructurales y están pensadas para acelerar desarrollo y pruebas.

### 9.1 Plantilla genérica NPCData

```xml
<NPCData xmlns="urn:npc:mx:np">
  <MessageHeader>
    <TransTimestamp>20250101123045</TransTimestamp>
    <Sender>IDA_DEL_EMISOR</Sender>
    <NumOfMessages>1</NumOfMessages>
  </MessageHeader>

  <NPCMessage>
    <!-- Sustituir por un elemento de mensaje válido -->
  </NPCMessage>
</NPCData>
```

### 9.2 Mensaje 1001 – Solicitud de Portación (PortReqMsgType)

```xml
<NPCData xmlns="urn:npc:mx:np">
  <MessageHeader>
    <TransTimestamp>20250101123045</TransTimestamp>
    <Sender>RIDA_ID</Sender>
    <NumOfMessages>1</NumOfMessages>
  </MessageHeader>

  <NPCMessage>
    <PortRequestMsg>
      <PortType>MOBILE</PortType>
      <SubscriberType>INDIVIDUAL</SubscriberType>
      <RecoveryFlagType>NO</RecoveryFlagType>
      <PortID>IDA202501011230450001</PortID>
      <FolioID>IDA2501011230ABCDE</FolioID>
      <Timestamp>20250101123045</Timestamp>
      <SubsReqTime>20250101121000</SubsReqTime>
      <ReqPortExecDate>20250102120000</ReqPortExecDate>
      <DIDA>DIDA_ID</DIDA>
      <DCR>DCR_ID</DCR>
      <RIDA>RIDA_ID</RIDA>
      <RCR>RCR_ID</RCR>
      <TotalPhoneNums>1</TotalPhoneNums>
      <Numbers>
        <Number>
          <StartNum>5512345678</StartNum>
          <EndNum>5512345678</EndNum>
        </Number>
      </Numbers>
      <Pin>1234</Pin>
      <Comments>Solicitud inicial</Comments>
      <!-- Si aplica -->
      <NumOfFiles>1</NumOfFiles>
      <AttachedFiles>
        <FileName>Solicitud.pdf</FileName>
      </AttachedFiles>
    </PortRequestMsg>
  </NPCMessage>
</NPCData>
```

### 9.3 Mensaje 1006 – Portación Programada (SchedulePortMsgType)

```xml
<NPCData xmlns="urn:npc:mx:np">
  <MessageHeader>
    <TransTimestamp>20250101140000</TransTimestamp>
    <Sender>RIDA_ID</Sender>
    <NumOfMessages>1</NumOfMessages>
  </MessageHeader>

  <NPCMessage>
    <SchedulePortMsg>
      <PortType>MOBILE</PortType>
      <SubscriberType>INDIVIDUAL</SubscriberType>
      <RecoveryFlagType>NO</RecoveryFlagType>
      <PortID>IDA202501011230450001</PortID>
      <Timestamp>20250101140000</Timestamp>
      <DIDA>DIDA_ID</DIDA>
      <DCR>DCR_ID</DCR>
      <RIDA>RIDA_ID</RIDA>
      <RCR>RCR_ID</RCR>
      <TotalPhoneNums>1</TotalPhoneNums>
      <Numbers>
        <Number>
          <StartNum>5512345678</StartNum>
          <EndNum>5512345678</EndNum>
        </Number>
      </Numbers>
      <PortExecDate>20250102120000</PortExecDate>
      <ReqPortExecDate>20250102120000</ReqPortExecDate>
      <Comments>Programación</Comments>
    </SchedulePortMsg>
  </NPCMessage>
</NPCData>
```

### 9.4 Mensaje 3001 – Solicitud de Cancelación (PortCancelReqMsgType)

```xml
<NPCData xmlns="urn:npc:mx:np">
  <MessageHeader>
    <TransTimestamp>20250101150000</TransTimestamp>
    <Sender>RIDA_ID</Sender>
    <NumOfMessages>1</NumOfMessages>
  </MessageHeader>

  <NPCMessage>
    <PortCancelReqMsg>
      <PortType>MOBILE</PortType>
      <SubscriberType>INDIVIDUAL</SubscriberType>
      <RecoveryFlagType>NO</RecoveryFlagType>
      <PortID>IDA202501011230450001</PortID>
      <Timestamp>20250101150000</Timestamp>
      <DIDA>DIDA_ID</DIDA>
      <DCR>DCR_ID</DCR>
      <RIDA>RIDA_ID</RIDA>
      <RCR>RCR_ID</RCR>
      <TotalPhoneNums>1</TotalPhoneNums>
      <Numbers>
        <Number>
          <StartNum>5512345678</StartNum>
          <EndNum>5512345678</EndNum>
        </Number>
      </Numbers>
      <Comments>Cancelación solicitada</Comments>
    </PortCancelReqMsg>
  </NPCMessage>
</NPCData>
```

---

## 10. Validaciones obligatorias que el agente debe implementar

### 10.1 Validaciones estructurales
- Validar XML contra XSD antes de enviar.  
- Validar que `Sender` coincida con el participante asociado al usuario SOAP.

### 10.2 Validaciones de negocio mínimas
- `PortID` y `FolioID` con longitud/formato correcto.  
- `TotalPhoneNums` debe coincidir con lista/rangos reportados.  
- No mezclar números Geográficos/No Geográficos en una misma solicitud.  
- Respetar reglas de adjuntos por tipo de suscriptor y tipo de portación.

### 10.3 Validaciones temporales
- Enforzar ventanas de temporizadores en lógica interna para:
  - Alertas proactivas.
  - Reintentos válidos.
  - Bloqueo de acciones fuera de tiempo.

---

## 11. Diseño interno sugerido

### 11.1 Entidades
- `PortabilityProcess`
  - `port_id`, `folio_id`, `port_type`, `subscriber_type`, `recovery_flag`
  - `rida_id`, `dida_id`, `rcr_id`, `dcr_id`
  - `current_state`, `timestamps`
- `PortabilityNumber`
  - `start_num`, `end_num`, `status_por_numero`
- `NpcMessage`
  - `message_id`, `direction`, `raw_xml`, `parsed_payload`, `received_at`, `sent_at`
- `Attachment`
  - `file_name`, `mime`, `size`, `checksum`, `storage_ref`

### 11.2 Componentes
- **Message Builder/Parser** por tipo de mensaje.  
- **Outbound Queue** con reintento idempotente.  
- **Inbound Dispatcher** basado en `MessageID`.  
- **State Orchestrator** que aplique reglas de transición ABD.  
- **Batch Reconciler** para archivos diarios.

### 11.3 Idempotencia
- Para mensajes salientes, usar `port_id + message_id + hash(payload)` como llave de deduplicación.  
- Para mensajes entrantes, persistir `raw_xml` y rechazar duplicados a nivel de aplicación.

---

## 12. Pruebas

El agente debe generar:

1. Pruebas unitarias de validadores de formato (PortID, FolioID, timestamps).  
2. Pruebas de integración con un mock SOAP del ABD.  
3. Pruebas de máquina de estados usando fixtures de mensajes 1001→1007 y 3001/3002.  
4. Pruebas de adjuntos, incluyendo límites de tamaño.

---

## 13. Checklist de entrega

- [ ] Implementación SOAP `processNPCMsg`.  
- [ ] Validadores XSD + reglas negocio.  
- [ ] Persistencia de mensajes y estados.  
- [ ] Manejo de adjuntos según reglas.  
- [ ] Temporizadores internos y alertas.  
- [ ] Reconciliación con archivos diarios.  
- [ ] Documentación técnica y diagramas internos.  

---

## 14. Notas finales

Esta guía está alineada a la versión 2.1 de los documentos de especificación.  
Si el ABD publica una versión posterior, el agente debe comparar cambios de:
- Mensajes soportados.  
- Estructuras XSD.  
- Reglas de temporizadores y adjuntos.  

