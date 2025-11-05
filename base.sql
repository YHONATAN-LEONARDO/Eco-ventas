
-- clear;sqlcmd -S localhost -d "db-sis" -U sa -P Liz12345 -Q "SELECT 'clientes' AS tabla, COUNT(*) AS cantidad FROM clientes UNION ALL SELECT 'compras', COUNT(*) FROM compras UNION ALL SELECT 'compras_detalle', COUNT(*) FROM compras_detalle UNION ALL SELECT 'contactos', COUNT(*) FROM contactos UNION ALL SELECT 'cuentas_bancarias', COUNT(*) FROM cuentas_bancarias UNION ALL SELECT 'empleados', COUNT(*) FROM empleados UNION ALL SELECT 'facturas', COUNT(*) FROM facturas UNION ALL SELECT 'metodos_pago', COUNT(*) FROM metodos_pago UNION ALL SELECT 'productos', COUNT(*) FROM productos UNION ALL SELECT 'proveedores', COUNT(*) FROM proveedores UNION ALL SELECT 'transacciones_pago', COUNT(*) FROM transacciones_pago UNION ALL SELECT 'usuarios', COUNT(*) FROM usuarios UNION ALL SELECT 'vendedor', COUNT(*) FROM vendedor UNION ALL SELECT 'ventas', COUNT(*) FROM ventas UNION ALL SELECT 'ventas_detalle', COUNT(*) FROM ventas_detalle;" -C

-- sqlcmd -S localhost -U sa -P 'Liz12345' -Q "BACKUP DATABASE [db-sis] TO DISK = N'/var/opt/mssql/data/db-sis.bak' WITH NOFORMAT, NOINIT, NAME = 'db-sis-full', SKIP, NOREWIND, NOUNLOAD, STATS = 10"  ; sudo ls -lh /var/opt/mssql/data/db-sis.bak

-- sqlcmd -S localhost -U sa -P 'Liz12345' -Q "RESTORE DATABASE [db-sis] FROM DISK = N'/var/opt/mssql/data/db-sis.bak' WITH REPLACE, STATS = 10"

CREATE DATABASE [db-sis];
GO

USE [db-sis];
GO

CREATE TABLE [dbo].[clientes] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [nombre] VARCHAR(100) NOT NULL,
    [apellido] VARCHAR(100) NULL,
    [correo] VARCHAR(150) NULL,
    [telefono] VARCHAR(50) NULL,
    [creado_en] DATETIME2 NOT NULL DEFAULT GETDATE()
);

CREATE TABLE [dbo].[compras] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [proveedor] VARCHAR(150) NOT NULL,
    [numero_factura] VARCHAR(50) NOT NULL,
    [fecha_compra] DATE NOT NULL,
    [total] DECIMAL(12,2) NOT NULL,
    [observacion] VARCHAR(300) NULL,
    [creado_en] DATETIME2 NOT NULL DEFAULT GETDATE()
);

CREATE TABLE [dbo].[compras_detalle] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [compra_id] INT NOT NULL,
    [producto_id] INT NOT NULL,
    [cantidad] INT NOT NULL,
    [precio_compra] DECIMAL(10,2) NOT NULL,
    CONSTRAINT FK_compras_detalle_compras FOREIGN KEY (compra_id) REFERENCES compras(id)
);

CREATE TABLE [dbo].[contactos] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [nombre] VARCHAR(150) NOT NULL,
    [correo] VARCHAR(150) NOT NULL,
    [asunto] VARCHAR(200) NOT NULL,
    [mensaje] VARCHAR(4000) NOT NULL,
    [ip] VARCHAR(64) NULL,
    [user_agent] VARCHAR(400) NULL,
    [creado_en] DATETIME2 NOT NULL DEFAULT GETDATE()
);

CREATE TABLE [dbo].[cuentas_bancarias] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [banco] VARCHAR(120) NOT NULL,
    [numero_cuenta] VARCHAR(60) NOT NULL,
    [tipo_cuenta] VARCHAR(40) NOT NULL,
    [titular] VARCHAR(150) NOT NULL,
    [ci_titular] VARCHAR(30) NULL,
    [creado_en] DATETIME2 NOT NULL DEFAULT GETDATE()
);

CREATE TABLE [dbo].[empleados] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [nombre] VARCHAR(100) NOT NULL,
    [apellido] VARCHAR(100) NOT NULL,
    [correo] VARCHAR(150) NOT NULL,
    [telefono] VARCHAR(50) NOT NULL,
    [rol] VARCHAR(20) NOT NULL,
    [password] VARCHAR(255) NOT NULL,
    [creado_en] DATETIME2 NOT NULL DEFAULT GETDATE()
);

CREATE TABLE [dbo].[facturas] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [venta_id] INT NOT NULL,
    [numero] VARCHAR(20) NOT NULL,
    [fecha_emision] DATETIME2 NOT NULL,
    [nit_cliente] VARCHAR(50) NULL,
    [razon_social] VARCHAR(200) NULL,
    [total] DECIMAL(12,2) NOT NULL,
    [observacion] VARCHAR(300) NULL,
    [lugar_entrega] NVARCHAR(255) NULL,
    [nombre_cliente] NVARCHAR(255) NULL,
    CONSTRAINT FK_facturas_ventas FOREIGN KEY (venta_id) REFERENCES ventas(id)
);

CREATE TABLE [dbo].[metodos_pago] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [nombre] VARCHAR(100) NOT NULL,
    [descripcion] VARCHAR(250) NULL
);

CREATE TABLE [dbo].[productos] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [titulo] VARCHAR(200) NOT NULL,
    [precio] DECIMAL(10,2) NOT NULL,
    [descripcion] VARCHAR(2000) NULL,
    [cantidad] INT NOT NULL,
    [categoria] VARCHAR(50) NOT NULL,
    [talla] VARCHAR(10) NOT NULL,
    [genero] VARCHAR(10) NOT NULL,
    [color] VARCHAR(50) NOT NULL,
    [vendedor] INT NOT NULL,
    [imagen] VARCHAR(255) NULL,
    [creado_en] DATETIME2 NOT NULL DEFAULT GETDATE(),
    [etiqueta] VARCHAR(20) NULL,
    [descuento] DECIMAL(5,2) NULL
);

CREATE TABLE [dbo].[proveedores] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [nombre] VARCHAR(150) NOT NULL,
    [telefono] VARCHAR(50) NULL,
    [correo] VARCHAR(150) NULL,
    [direccion] VARCHAR(200) NULL,
    [creado_en] DATETIME2 NOT NULL DEFAULT GETDATE(),
    [actualizado_en] DATETIME2 NULL
);

CREATE TABLE [dbo].[transacciones_pago] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [venta_id] INT NOT NULL,
    [metodo_pago_id] INT NOT NULL,
    [cuenta_id] INT NULL,
    [referencia] VARCHAR(100) NULL,
    [monto] DECIMAL(12,2) NOT NULL,
    [estado] VARCHAR(20) NOT NULL,
    [fecha_pago] DATETIME2 NOT NULL,
    CONSTRAINT FK_transacciones_pago_ventas FOREIGN KEY (venta_id) REFERENCES ventas(id),
    CONSTRAINT FK_transacciones_pago_metodos_pago FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id)
);

CREATE TABLE [dbo].[usuarios] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [nombre] VARCHAR(150) NOT NULL,
    [correo] VARCHAR(150) NOT NULL,
    [password_hash] VARCHAR(255) NOT NULL,
    [rol] VARCHAR(20) NOT NULL,
    [creado_en] DATETIME2 NOT NULL DEFAULT GETDATE()
);

CREATE TABLE [dbo].[vendedor] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [nombre] VARCHAR(100) NOT NULL,
    [apellido] VARCHAR(100) NULL,
    [correo] VARCHAR(150) NULL,
    [telefono] VARCHAR(50) NULL,
    [activo] BIT NOT NULL,
    [creado_en] DATETIME2 NOT NULL DEFAULT GETDATE()
);

CREATE TABLE [dbo].[ventas] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [fecha] DATETIME2 NOT NULL DEFAULT GETDATE(),
    [total] DECIMAL(12,2) NOT NULL,
    [estado] VARCHAR(20) NOT NULL,
    [cliente_id] INT NULL,
    [vendedor] INT NULL,
    [direccion_entrega] VARCHAR(200) NULL,
    [ciudad_entrega] VARCHAR(100) NULL,
    [referencia_entrega] VARCHAR(200) NULL,
    [fecha_entrega] DATETIME2 NULL,
    [estado_entrega] VARCHAR(50) NOT NULL,
    CONSTRAINT FK_ventas_clientes FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    CONSTRAINT FK_ventas_vendedor FOREIGN KEY (vendedor) REFERENCES vendedor(id)
);

CREATE TABLE [dbo].[ventas_detalle] (
    [id] INT NOT NULL PRIMARY KEY IDENTITY(1,1),
    [venta_id] INT NOT NULL,
    [producto_id] INT NOT NULL,
    [cantidad] INT NOT NULL,
    [precio_unitario] DECIMAL(10,2) NOT NULL,
    [descuento] DECIMAL(10,2) NULL,
    CONSTRAINT FK_ventas_detalle_ventas FOREIGN KEY (venta_id) REFERENCES ventas(id),
    CONSTRAINT FK_ventas_detalle_productos FOREIGN KEY (producto_id) REFERENCES productos(id)
);
